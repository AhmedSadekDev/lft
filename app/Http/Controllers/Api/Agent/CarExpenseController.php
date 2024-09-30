<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\CarExpenseRequest;
use App\Http\Requests\Api\Agent\GeneralExpenseRequest;
use App\Http\Requests\Api\Agent\ReservationExpenseRequest;
use App\Http\Resources\Api\Agent\ExpenseResource;
use App\Models\Agent;
use App\Models\AgentExpense;
use App\Models\DeliveryPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CarExpenseController extends Controller
{

    public function make_car_expenses(CarExpenseRequest $request)
    {
        try {

            $agent = auth()->guard('agent')->user();
            $delivery_policy = DeliveryPolicy::whereId($request->delivery_policy_id)->first();

            if($delivery_policy->is_settled == 1){
                return $this->returnError(200, __('main.delivery_policy is settled'));
            }

            $data = $request->validated();
            $data["delivery_policy_id"] = $request->delivery_policy_id;
            $data["type"] = 2;
          $AgentExpense =   AgentExpense::create($data);

            $this->saveLogActivity($agent->id, Agent::class,$AgentExpense->id,AgentExpense::class);




            return $this->returnResponseSuccessMessage(__('alerts.success'), 200);
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }

}
