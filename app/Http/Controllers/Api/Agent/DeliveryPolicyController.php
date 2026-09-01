<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\DeliveryPolicyDetailsRequest;
use App\Http\Requests\Api\Agent\DeliveryPolicyRequest;
use App\Http\Resources\Api\Agent\CarExpenseResource;
use App\Http\Resources\Api\Agent\DeliveryPolicyDetailsResource;
use App\Http\Resources\Api\Agent\DeliveryPolicyResource;
use App\Http\Resources\Api\Agent\MoneyTransferResource;
use App\Models\Agent;
use App\Models\BookingContainer;
use App\Models\CompanyTransportation;
use App\Models\DeliveryPolicy;
use App\Models\CitiesAndRegions;
use App\Models\Image;
use App\Models\MoneyTransfer;
use App\Services\AgentImageUploadService;
use App\Traits\HandlesAgentImageUploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryPolicyController extends Controller
{
    use HandlesAgentImageUploads;

    private const IMAGE_FOLDER = 'delivery_policies';

    public function create_delivery_policy(DeliveryPolicyRequest $request)
    {
        if ($response = $this->rejectIfPayloadTooLarge($request)) {
            return $response;
        }

        try {
            // dd($request->all());
            $agent = auth()->guard('agent')->user();

            $bookingContainer = BookingContainer::find($request->booking_container_ids[0]);

            if ($bookingContainer->delivery_policies->count() > 0) {
                return $this->returnResponseSuccessMessage(__('This Container Alread Has Delivery Plicy'), 200);
            }

            $officeCommission = $request->office_commission ?? 0;
            $actualDeduction = $request->value - $officeCommission; // المبلغ الفعلي المخصوم

            // التحقق من أن المحفظة تكفي للمبلغ الفعلي المخصوم
            if ($agent->wallet < $actualDeduction) {
                return $this->returnError(200, __('main.you dont have enougth money'));
            }

            $city = CitiesAndRegions::find($request->loading_id);
            
            DB::beginTransaction();

            //create delivery_policy
            $delivery_policy_data["car_id"] = $request->car_id;
            $delivery_policy_data["driver_id"] = $request->driver_id;
            $delivery_policy_data["date"] = $request->date;
            $delivery_policy_data["address"] = $city->address . " " . $city->city;
            $delivery_policy_data["office_commission"] = $officeCommission;
            $delivery_policy = DeliveryPolicy::create($delivery_policy_data);

            $delivery_policy->booking_containers()->attach($request->booking_container_ids);

            if ($bookingContainer) {
                if ($request->filled("departure_id")) {
                    $bookingContainer->update([
                        'departure_id' => $request->departure_id
                    ]);
                }

                if ($request->filled("loading_id")) {
                    $bookingContainer->update([
                        'loading_id' => $request->loading_id
                    ]);
                }

                if ($request->filled("aging_id")) {
                    $bookingContainer->update([
                        'aging_id' => $request->aging_id
                    ]);
                }
            }

            if ($bookingContainer->departure_id && $bookingContainer->loading_id && $bookingContainer->aging_id) {
                $check = CompanyTransportation::where('container_id', $bookingContainer->container_id)
                    ->where('departure_id', $bookingContainer->departure_id)
                    ->where('loading_id', $bookingContainer->loading_id)
                    ->where('aging_id', $bookingContainer->aging_id)
                    ->first();

                if ($check) {
                    $bookingContainer->update(['price' => $check->price]);
                }
            }

            // إنشاء معاملة العهدة للسائق (type 3)
            $data["value"] = $request->value;
            $data["type"] = 3;
            $data["transferer_type"] = "App\Models\Agent";
            $data["transferer_id"] = $agent->id;
            $data["transfered_type"] = "App\Models\Driver";
            $data["transfered_id"] = $request->driver_id;
            $data["delivery_policy_id"] = $delivery_policy->id;
            $data["date"] = $request->date ?? now();
            $data["address"] = $city->address . " " . $city->city;
            $moneyTransfer = MoneyTransfer::create($data);

            $this->saveLogActivity($agent->id, Agent::class, $moneyTransfer->id, MoneyTransfer::class);

            // إنشاء معاملة دخان المكتب (type 5) - إرجاع للمندوب
            if ($officeCommission > 0) {
                $commissionData["value"] = $officeCommission;
                $commissionData["type"] = 5; // نوع جديد: دخان المكتب
                $commissionData["transferer_type"] = "App\Models\Driver";
                $commissionData["transferer_id"] = $request->driver_id;
                $commissionData["transfered_type"] = "App\Models\Agent";
                $commissionData["transfered_id"] = $agent->id;
                $commissionData["delivery_policy_id"] = $delivery_policy->id;
                $commissionData["date"] = $request->date ?? now();
                $commissionData["address"] = $city->address . " " . $city->city;
                $officeCommissionTransfer = MoneyTransfer::create($commissionData);

                $this->saveLogActivity($agent->id, Agent::class, $officeCommissionTransfer->id, MoneyTransfer::class);
            }

            if ($request->hasFile('image') || $request->has('image')) {
                $stored = $this->storeMorphImageFromRequest(
                    $request,
                    'image',
                    self::IMAGE_FOLDER,
                    $delivery_policy->id,
                    DeliveryPolicy::class,
                    false
                );
                if ($stored instanceof \Illuminate\Http\JsonResponse) {
                    DB::rollBack();
                    return $stored;
                }
            }

            // خصم المبلغ الفعلي فقط (القيمة - دخان المكتب)
            $agent->update([
                'wallet' => $agent->wallet - $actualDeduction
            ]);

            DB::commit();

            return $this->returnResponseSuccessMessage(__('alerts.success'), 200);
        } catch (\Exception $Exception) {
            DB::rollBack();
            return $this->returnError(500, $Exception->getMessage());
        }
    }
    public function fetch_delivery_policies()
    {
        try {

            $agent = auth()->guard('agent')->user();


            $delivery_policies = DeliveryPolicy::with([
                'car',
                'driver',
                'money_transfer',
                'image',
                'booking_containers' => function ($query) {
                    $query->with(['booking', 'branch']);
                }
            ])->whereHas("money_transfer", function ($q) use ($agent) {
                return $q->where("transferer_id", $agent->id);
            })->get();

            $data = DeliveryPolicyResource::collection($delivery_policies);


            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(500, $Exception->getMessage());
        }
    }
    public function delivery_policy_details(DeliveryPolicyDetailsRequest $request)
    {
        try {

            $agent = auth()->guard('agent')->user();

            $delivery_policy = DeliveryPolicy::whereId($request->delivery_policy_id)->first();


            $data = new DeliveryPolicyDetailsResource($delivery_policy);


            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(500, $Exception->getMessage());
        }
    }

    public function delivery_policy_expenses(DeliveryPolicyDetailsRequest $request)
    {
        try {

            $agent = auth()->guard('agent')->user();

            $delivery_policy = DeliveryPolicy::whereId($request->delivery_policy_id)->first();

            $data["car_expenses"] = CarExpenseResource::collection($delivery_policy->car_expenses);
            $data["driver_dues"] = $delivery_policy->driver_dues;
            $data["money_transfer"] = new MoneyTransferResource($delivery_policy->money_transfer);
            $data["settled_money_transfer"] = $delivery_policy->settled_money_transfer ? new MoneyTransferResource($delivery_policy->settled_money_transfer) : null;


            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(500, $Exception->getMessage());
        }
    }
    public function settle_delivery_policy(DeliveryPolicyDetailsRequest $request)
    {
        try {

            $agent = auth()->guard('agent')->user();

            $delivery_policy = DeliveryPolicy::whereId($request->delivery_policy_id)->first();

            if ($delivery_policy->is_settled == 1) {
                return $this->returnError(200, __('main.delivery_policy is settled'));
            }

            if ($delivery_policy->driver_dues < 0 && $agent->wallet < abs($delivery_policy->driver_dues)) {
                return $this->returnError(200, __('main.you dont have enougth money'));
            }



            $delivery_policy->update([
                "is_settled" => 1
            ]);
            //create MoneyTransfer

            $data["value"] = $delivery_policy->driver_dues;
            $data["type"] = 4;
            $data["transferer_type"] = "App\Models\Agent";
            $data["transfered_type"] = "App\Models\Driver";
            $data["transferer_id"] = $delivery_policy->driver_id;
            $data["transfered_id"] = $agent->id;
            $data["delivery_policy_id"] = $delivery_policy->id;
            $moneyTransfer = MoneyTransfer::create($data);

            $this->saveLogActivity($agent->id, Agent::class, $moneyTransfer->id, MoneyTransfer::class);

            if ($request->hasFile('image') || $request->has('image')) {
                $stored = $this->storeMorphImageFromRequest(
                    $request,
                    'image',
                    self::IMAGE_FOLDER,
                    $delivery_policy->id,
                    DeliveryPolicy::class,
                    false
                );
                if ($stored instanceof \Illuminate\Http\JsonResponse) {
                    return $stored;
                }
            }

            return $this->returnResponseSuccessMessage(__('alerts.success'), 200);
        } catch (\Exception $Exception) {
            return $this->returnError(500, $Exception->getMessage());
        }
    }

    public function update_delivery_policy(Request $request)
    {
        if ($response = $this->rejectIfPayloadTooLarge($request)) {
            return $response;
        }

        try {
            $request->validate([
                'id' => 'required|exists:delivery_policies,id',
                'value' => 'sometimes|numeric',
                'office_commission' => 'nullable|numeric|min:0',
                'car_id'  => 'sometimes|exists:cars,id',
                'driver_id'  => 'sometimes|exists:drivers,id',
                'booking_container_ids' => 'sometimes|array',
                'booking_container_ids.*' => 'exists:booking_containers,id',
                'departure_id' => 'sometimes|exists:cities_and_regions,id',
                'loading_id' => 'sometimes|exists:cities_and_regions,id',
                'aging_id' => 'sometimes|exists:cities_and_regions,id',
                'date' => 'sometimes|date',
                'image' => 'sometimes',
            ]);

            $agent = auth()->guard('agent')->user();
            $delivery_policy = DeliveryPolicy::findOrFail($request->id);

            if ($delivery_policy->is_settled == 1) {
                return $this->returnError(200, __('main.delivery_policy is settled'));
            }

            if (($delivery_policy->money_transfer?->transferer_id) !== $agent->id) {
                return $this->returnError(403, __('main.not_allowed'));
            }

            $oldValue = (float) ($delivery_policy->money_transfer?->value ?? 0);
            $oldCommission = (float) ($delivery_policy->office_commission ?? 0);
            $oldActualDeduction = $oldValue - $oldCommission; // المبلغ القديم المخصوم فعلياً

            $newValue = $request->has('value') ? (float) $request->value : $oldValue;
            $newCommission = $request->has('office_commission') ? (float) $request->office_commission : $oldCommission;
            $newActualDeduction = $newValue - $newCommission; // المبلغ الجديد المخصوم فعلياً

            $actualDiff = $newActualDeduction - $oldActualDeduction; // الفرق الفعلي

            if ($actualDiff > 0 && $agent->wallet < $actualDiff) {
                return $this->returnError(200, __('main.you dont have enougth money'));
            }

            DB::beginTransaction();

            if ($request->filled('loading_id')) {
                $city = CitiesAndRegions::find($request->loading_id);
                if ($city) {
                    $delivery_policy->address = $city->address . ' ' . $city->city;
                }
            }

            if ($request->filled('car_id')) {
                $delivery_policy->car_id = $request->car_id;
            }
            if ($request->filled('driver_id')) {
                $delivery_policy->driver_id = $request->driver_id;
            }
            if ($request->filled('date')) {
                $delivery_policy->date = $request->date;
            }
            if ($request->has('office_commission')) {
                $delivery_policy->office_commission = $newCommission;
            }
            $delivery_policy->save();

            if ($request->filled('booking_container_ids')) {
                $delivery_policy->booking_containers()->sync($request->booking_container_ids);
            }

            // تحديث معاملة العهدة (type 3)
            if ($delivery_policy->money_transfer) {
                $delivery_policy->money_transfer->update([
                    'value' => $newValue,
                    'transfered_id' => $request->input('driver_id', $delivery_policy->money_transfer->transfered_id),
                ]);
            }

            // تحديث أو إنشاء معاملة دخان المكتب (type 5)
            $officeCommissionTransfer = MoneyTransfer::where('delivery_policy_id', $delivery_policy->id)
                ->where('type', 5)
                ->first();

            if ($newCommission > 0) {
                if ($officeCommissionTransfer) {
                    $officeCommissionTransfer->update(['value' => $newCommission]);
                } else {
                    // إنشاء معاملة دخان المكتب إذا لم تكن موجودة
                    $commissionData["value"] = $newCommission;
                    $commissionData["type"] = 5;
                    $commissionData["transferer_type"] = "App\Models\Driver";
                    $commissionData["transferer_id"] = $delivery_policy->driver_id;
                    $commissionData["transfered_type"] = "App\Models\Agent";
                    $commissionData["transfered_id"] = $agent->id;
                    $commissionData["delivery_policy_id"] = $delivery_policy->id;
                    $commissionData["date"] = $delivery_policy->date ?? now();
                    $commissionData["address"] = $delivery_policy->address;
                    MoneyTransfer::create($commissionData);
                }
            } elseif ($officeCommissionTransfer && $newCommission == 0) {
                // حذف معاملة دخان المكتب إذا أصبحت صفر
                $officeCommissionTransfer->delete();
            }

            // تحديث محفظة المندوب بناءً على الفرق الفعلي
            if ($actualDiff !== 0.0) {
                $agent->update(['wallet' => $agent->wallet - $actualDiff]);
            }

            if ($request->hasFile('image') || $request->has('image')) {
                $upload = $this->resolveAgentImageUpload(
                    $request,
                    'image',
                    self::IMAGE_FOLDER,
                    AgentImageUploadService::STORAGE_DISK,
                    false
                );

                if ($upload instanceof \Illuminate\Http\JsonResponse) {
                    DB::rollBack();
                    return $upload;
                }

                if (! $upload['skipped'] && ! empty($upload['path'])) {
                    if ($delivery_policy->image) {
                        $delivery_policy->image->update(['image' => $upload['path']]);
                    } else {
                        $this->attachStoredImage($upload['path'], $delivery_policy->id, DeliveryPolicy::class);
                    }
                }
            }

            DB::commit();

            return $this->returnResponseSuccessMessage(__('alerts.success'), 200);
        } catch (\Exception $Exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return $this->returnError(500, $Exception->getMessage());
        }
    }

    public function delete_delivery_policy(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:delivery_policies,id',
            ]);

            $agent = auth()->guard('agent')->user();
            $delivery_policy = DeliveryPolicy::findOrFail($request->id);

            if ($delivery_policy->is_settled == 1) {
                return $this->returnError(200, __('main.delivery_policy is settled'));
            }

            if (($delivery_policy->money_transfer?->transferer_id) !== $agent->id) {
                return $this->returnError(403, __('main.not_allowed'));
            }

            DB::beginTransaction();

            // حساب المبلغ الذي يجب إرجاعه (القيمة - دخان المكتب)
            $valueToRefund = (float) ($delivery_policy->money_transfer?->value ?? 0);
            $officeCommission = (float) ($delivery_policy->office_commission ?? 0);
            $actualRefund = $valueToRefund - $officeCommission; // المبلغ الفعلي الذي تم خصمه

            if ($actualRefund > 0) {
                $agent->update(['wallet' => $agent->wallet + $actualRefund]);
            }

            if ($delivery_policy->image) {
                $delivery_policy->image()->delete();
            }

            $delivery_policy->booking_containers()->detach();

            // حذف معاملة العهدة (type 3)
            if ($delivery_policy->money_transfer) {
                $delivery_policy->money_transfer()->delete();
            }

            // حذف معاملة دخان المكتب (type 5) إن وجدت
            MoneyTransfer::where('delivery_policy_id', $delivery_policy->id)
                ->where('type', 5)
                ->delete();

            $delivery_policy->delete();

            DB::commit();

            return $this->returnResponseSuccessMessage(__('alerts.success'), 200);
        } catch (\Exception $Exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return $this->returnError(500, $Exception->getMessage());
        }
    }
}
