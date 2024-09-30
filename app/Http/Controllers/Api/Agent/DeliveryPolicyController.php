<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\DeliveryPolicyDetailsRequest;
use App\Http\Requests\Api\Agent\DeliveryPolicyRequest;
use App\Http\Requests\Api\Agent\TransferAgentRequest;
use App\Http\Resources\Api\Agent\CarExpenseResource;
use App\Http\Resources\Api\Agent\DeliveryPolicyDetailsResource;
use App\Http\Resources\Api\Agent\DeliveryPolicyResource;
use App\Http\Resources\Api\Agent\MoneyTransferResource;
use App\Http\Resources\Api\Agent\NameResource;
use App\Models\Agent;
use App\Models\BookingContainer;
use App\Models\CompanyTransportation;
use App\Models\DeliveryPolicy;
use App\Models\Image;
use App\Models\MoneyTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DeliveryPolicyController extends Controller
{

    public function create_delivery_policy(DeliveryPolicyRequest $request)
    {
        try {

            $agent = auth()->guard('agent')->user();
            
            
             $bookingContainer = BookingContainer::find($request->booking_container_ids[0]);
            
            if($bookingContainer->delivery_policies->count() > 0) {
                 return $this->returnResponseSuccessMessage(__('This Container Alread Has Delivery Plicy'), 200);
            }
            

            if ($agent->wallet < $request->value) {
                return $this->returnError(200, __('main.you dont have enougth money'));
            }

            //create delivery_policy
            $delivery_policy_data["car_id"] = $request->car_id;
            $delivery_policy_data["driver_id"] = $request->driver_id;
            $delivery_policy = DeliveryPolicy::create($delivery_policy_data);

            $delivery_policy->booking_containers()->attach($request->booking_container_ids);

            //create MoneyTransfer

           
            
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



            

            $data["value"] = $request->value;
            $data["type"] = 3;
            $data["transferer_type"] = "App\Models\Agent";
            $data["transferer_id"] = $agent->id;
            $data["transfered_type"] = "App\Models\Driver";
            $data["transfered_id"] = $request->driver_id;
            $data["delivery_policy_id"] = $delivery_policy->id;
            $moneyTransfer = MoneyTransfer::create($data);

            $this->saveLogActivity($agent->id, Agent::class, $moneyTransfer->id, MoneyTransfer::class);


            if ($request->image) {
                $image_data["image"] = $request->image;
                $image_data["imageable_id"] = $delivery_policy->id;
                $image_data["imageable_type"] = "App\Models\DeliveryPolicy";
                Image::create($image_data);
            }

            $agent->update([
                'wallet' => $agent->wallet -= $request->value
            ]);


            return $this->returnResponseSuccessMessage(__('alerts.success'), 200);
        } catch (\Exception $Exception) {
            return $this->returnError(500, $Exception->getMessage());
        }
    }
    public function fetch_delivery_policies()
    {
        try {

            $agent = auth()->guard('agent')->user();


            $delivery_policies = DeliveryPolicy::whereHas("money_transfer", function ($q) use ($agent) {
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

            if ($request->image) {
                $image_data["image"] = $request->image;
                $image_data["imageable_id"] = $delivery_policy->id;
                $image_data["imageable_type"] = "App\Models\DeliveryPolicy";
                Image::create($image_data);
            }


            return $this->returnResponseSuccessMessage(__('alerts.success'), 200);
        } catch (\Exception $Exception) {
            return $this->returnError(500, $Exception->getMessage());
        }
    }
}
