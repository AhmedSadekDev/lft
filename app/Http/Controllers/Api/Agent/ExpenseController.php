<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\GeneralExpenseRequest;
use App\Http\Requests\Api\Agent\ReservationExpenseRequest;
use App\Http\Resources\Api\Agent\ExpenseResource;
use App\Models\Agent;
use App\Traits\ImagesTrait;
use App\Models\AgentExpense;
use App\Models\BookingContainer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Image;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    use ImagesTrait;

    public function update_expense(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:agent_expenses,id',
                'value' => 'required|numeric',
                'service_id' => 'sometimes|exists:services,id',
                'notes' => 'sometimes',
                'booking_container_id' => 'sometimes|exists:booking_containers,id',
                'type_id' => 'sometimes',
                'image' => 'sometimes|mimes:png,jpg,jpeg|max:10000',
            ]);

            $agent = auth()->guard('agent')->user();
            $expense = AgentExpense::findOrFail($request->id);

            if ($expense->agent_id !== $agent->id) {
                return $this->returnError(403, __('main.not_allowed'));
            }

            // Adjust wallet by difference
            $oldValue = (float) $expense->value;
            $newValue = (float) $request->value;
            $diff = $newValue - $oldValue;

            if ($diff > 0 && $agent->wallet < $diff) {
                return $this->returnError(200, __('main.you dont have enougth money'));
            }

            DB::beginTransaction();

            // Handle image replacement if provided
            $imageName = $expense->image_agent_expenses;
            if ($request->hasFile('image')) {
                $newImageName = time() . '_expenses.' . $request->image->extension();
                $oldPath = $imageName ? 'Admin/images/expenses/' . $imageName : null;
                $this->uploadImage($request->image, $newImageName, 'expenses', $oldPath);
                $imageName = $newImageName;
            }

            // Update expense fields
            $expense->update([
                'value' => $newValue,
                'service_id' => $request->input('service_id', $expense->service_id),
                'notes' => $request->input('notes', $expense->notes),
                'booking_container_id' => $request->input('booking_container_id', $expense->booking_container_id),
                'type_id' => $request->input('type_id', $expense->type_id),
                'image_agent_expenses' => $imageName,
            ]);

            // Update wallet
            if ($diff !== 0.0) {
                $agent->update(['wallet' => $agent->wallet - $diff]);
            }

            DB::commit();

            return $this->returnResponseSuccessMessage(__('alerts.success'), 200);
        } catch (\Exception $Exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return $this->returnError(401, $Exception->getMessage());
        }
    }

    public function delete_expense(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:agent_expenses,id',
            ]);

            $agent = auth()->guard('agent')->user();
            $expense = AgentExpense::with('delivery_policy.money_transfer')->findOrFail($request->id);

            if ($expense->agent_id !== $agent->id) {
                return $this->returnError(403, __('main.not_allowed'));
            }

            DB::beginTransaction();

            // إذا كان المصروف مرتبط بعهدة، إرجاع القيمة للعهدة
            if ($expense->delivery_policy_id && $expense->delivery_policy) {
                $deliveryPolicy = $expense->delivery_policy;

                // التحقق من أن العهدة لم يتم تسويتها
                if ($deliveryPolicy->is_settled == 1) {
                    DB::rollBack();
                    return $this->returnError(200, __('main.delivery_policy is settled'));
                }

                // إرجاع القيمة للعهدة عن طريق زيادة قيمة money_transfer
                if ($deliveryPolicy->money_transfer) {
                    $moneyTransfer = $deliveryPolicy->money_transfer;
                    $moneyTransfer->update([
                        'value' => $moneyTransfer->value + $expense->value
                    ]);
                }
            } else {
                // إذا لم يكن مرتبط بعهدة، إرجاع القيمة للمحفظة
                $agent->update(['wallet' => $agent->wallet + $expense->value]);
            }

            // delete image file if exists
            if ($expense->image_agent_expenses) {
                $path = public_path('Admin/images/expenses/' . $expense->image_agent_expenses);
                if (file_exists($path)) {
                    @unlink($path);
                }
            }

            $expense->delete();

            DB::commit();

            return $this->returnResponseSuccessMessage(__('alerts.success'), 200);
        } catch (\Exception $Exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return $this->returnError(401, $Exception->getMessage());
        }
    }
    public function fetch_financial_custody()
    {
        try {

            $agent = auth()->guard('agent')->user();

            $total_financial_custody  = (int)$agent->total_wallet;
            $spented_financial_custody  = $agent->spented_financial_custody;
            $remaining_financial_custody  = (int)$agent->wallet;

            //reponse
            $data["total_financial_custody"] = $total_financial_custody;
            $data["spented_financial_custody"] = $spented_financial_custody;
            $data["remaining_financial_custody"] = $remaining_financial_custody;


            return $this->returnAllData((object) $data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }
    public function make_general_expenses(GeneralExpenseRequest $request)
    {
        try {

            $agent = auth()->guard('agent')->user();

            if ($agent->wallet < $request->value) {
                return $this->returnError(200, __('main.you dont have enougth money'));
            }
            $imageName = null;
            if($request->image)
            {
                $imageName = time() . '_expenses.' . $request->image->extension();
                $this->uploadImage($request->image, $imageName, 'expenses');
            }
            $data = $request->validated();
            $data["agent_id"] = $agent->id;
            $data["type"] = 1;
            $data["image_agent_expenses"] = $imageName;
            $data['type_id'] = $request->type_id;
            $data['booking_container_id'] = $request->booking_container_id;

            // تعيين booking_id من booking_container_id
            if ($request->booking_container_id) {
                $bookingContainer = BookingContainer::find($request->booking_container_id);
                if ($bookingContainer) {
                    $data['booking_id'] = $bookingContainer->booking_id;
                }
            }

            $expense =  AgentExpense::create($data);

            $agent->update(['wallet' => $agent->wallet - $request->value]);

            $this->saveLogActivity($agent->id, Agent::class, $expense->id, AgentExpense::class);


            return $this->returnResponseSuccessMessage(__('alerts.Expense saved successfully'), 200);
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }
    public function make_reservation_expenses(ReservationExpenseRequest $request)
    {
        try {
            $agent = auth()->guard('agent')->user();

            if ($agent->wallet < $request->value) {
                return $this->returnError(200, __('main.you dont have enougth money'));
            }

            $data = $request->validated();
            $imageName = null;
            if($request->image)
            {
                $imageName = time() . '_expenses.' . $request->image->extension();
                $this->uploadImage($request->image, $imageName, 'expenses');
            }
            $data["agent_id"] = $agent->id;
            $data["type"] = 2;
            $data["image_agent_expenses"] = $imageName;
            $data['type_id'] = $request->type_id;
            $data['booking_container_id'] = $request->booking_container_id;

            // تعيين booking_id من booking_container_id
            if ($request->booking_container_id) {
                $bookingContainer = BookingContainer::find($request->booking_container_id);
                if ($bookingContainer) {
                    $data['booking_id'] = $bookingContainer->booking_id;
                }
            }

            $expense = AgentExpense::create([
                'agent_id' => $agent->id,
                'type' => 2,
                'image_agent_expenses' => $imageName,
                'type_id' => $request->type_id,
                'service_id' => $request->service_id,
                'value' => $request->value,
                'booking_container_id' => $request->booking_container_id,
                'booking_id' => $data['booking_id'] ?? null,
            ]);

            $agent->update(['wallet' => $agent->wallet - $request->value]);

            $this->saveLogActivity($agent->id, Agent::class, $expense->id, AgentExpense::class);

            return $this->returnResponseSuccessMessage(__('alerts.Expense saved successfully'), 200);
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }
    public function fetch_all_expenses()
    {
        try {
            $type = request()->type;
            $agent = auth()->guard('agent')->user();
            $financial_custodies = collect();
            $expenses = collect();

            if ($type == 1) {
                $financial_custodies = $agent->sended_financial_custodies()
                    ->where("delivery_policy_id", "!=", null)
                    ->whereDate("created_at", now())
                    ->get();
                $expenses = $agent->expenses()
                    ->whereDate("created_at", now())
                    ->where("delivery_policy_id", "!=", null)
                    ->get();
            } elseif ($type == 2) {
                $expenses = $agent->expenses()
                    ->whereDate("created_at", now())
                    ->where("type", 2)
                    ->get();
            } elseif (request()->booking_id) {
                $bookingId = request()->booking_id;

                $expenses = $agent->expenses()
                    ->where(function ($query) use ($bookingId) {
                        // المصروفات التي لها booking_id مباشرة يساوي الحجز المطلوب
                        $query->where('booking_id', $bookingId)
                              // أو المصروفات التي لها booking_container_id مرتبط بالحجز المطلوب فقط
                              ->orWhereHas('bookingContainer', function ($q) use ($bookingId) {
                                  $q->where('booking_id', $bookingId);
                              });
                    })
                    // استبعاد المصروفات التي لها booking_id مختلف عن الحجز المطلوب
                    ->where(function ($query) use ($bookingId) {
                        $query->whereNull('booking_id')
                              ->orWhere('booking_id', $bookingId);
                    })
                    ->get();
            } elseif (request()->delivery_policy_id) {
                $expenses = $agent->expenses()
                    ->where("delivery_policy_id", request()->delivery_policy_id)
                    ->get();
            } else {
                $financial_custodies = $agent->sended_financial_custodies()
                    ->whereDate("created_at", now())
                    ->get();
                $expenses = $agent->expenses()
                    ->whereDate("created_at", now())
                    ->get();
            }

            $merged = $financial_custodies->concat($expenses);

            $ordered = $merged->sortBy('created_at')->values();

            $data = ExpenseResource::collection($ordered);

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $exception) {
            return $this->returnError(401, $exception->getMessage());
        }
    }
    public function fetch_latest_expenses()
    {
        try {

            $agent = auth()->guard('agent')->user();

            $financial_custodies = $agent->sended_financial_custodies()->whereDate("created_at", now())->get();
            $expenses = $agent->expenses()->whereDate("created_at", now())->get();

            // Merge the collections
            $merged = $financial_custodies->concat($expenses);

            // Order the merged collection by the created_at timestamp
            $ordered = $merged->sortBy('created_at')->take(3);

            $data = ExpenseResource::collection($ordered);

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }
}
