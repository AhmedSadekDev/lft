<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\CarExpenseRequest;
use App\Traits\ImagesTrait;
use App\Models\Agent;
use App\Models\AgentExpense;
use App\Models\DeliveryPolicy;

class CarExpenseController extends Controller
{
    use ImagesTrait;

    public function make_car_expenses(CarExpenseRequest $request)
    {
        try {

            $agent = auth()->guard('agent')->user();
            $delivery_policy = DeliveryPolicy::with(['booking_containers:id,booking_id'])
                ->whereId($request->delivery_policy_id)
                ->first();

            if (! $delivery_policy) {
                return $this->returnError(404, __('main.not_found'));
            }

            if ($delivery_policy->is_settled == 1) {
                return $this->returnError(200, __('main.delivery_policy is settled'));
            }

            $container = $delivery_policy->booking_containers->first();
            if (! $container) {
                return $this->returnError(200, __('main.container_not_written_yet'));
            }

            $data = $request->validated();
            unset($data['image']);
            $data['agent_id'] = $agent->id;
            $data['value'] = $request->value;
            $data['delivery_policy_id'] = $request->delivery_policy_id;
            $data['booking_container_id'] = $container->id;
            $data['booking_id'] = $container->booking_id;
            $data['type'] = AgentExpense::carExpenses;

            $imageName = null;
            if ($request->hasFile('image')) {
                $imageName = time() . '_car_expenses.' . $request->image->extension();
                $this->uploadImage($request->image, $imageName, 'expenses');
            }
            $data['image_agent_expenses'] = $imageName;

            $AgentExpense = AgentExpense::create($data);

            $this->saveLogActivity($agent->id, Agent::class, $AgentExpense->id, AgentExpense::class);




            return $this->returnResponseSuccessMessage(__('alerts.success'), 200);
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }

}
