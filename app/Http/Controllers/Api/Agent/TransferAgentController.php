<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\TransferAgentRequest;
use App\Http\Resources\Api\Agent\NameResource;
use App\Models\Agent;
use App\Models\AppNotification;
use App\Models\MoneyTransfer;
use App\Services\SaveNotification;
use App\Services\SendNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TransferAgentController extends Controller
{
    public function fetch_agents()
    {
        try {

            $agent = auth()->guard('agent')->user();

            $agents = Agent::where("id", "!=", $agent->id)->ofFilter()->get();

            $data = NameResource::collection($agents);


            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }
    public function transfer_to_agent(TransferAgentRequest $request)
    {
        try {

            $agent = auth()->guard('agent')->user();

            if ($agent->wallet < $request->value) {
                return $this->returnError(200, __('main.you dont have enougth money'));
            }
            $value = $request->value;
            $data["value"] = $request->value;
            $data["type"] = 2;
            $data["transferer_type"] = "App\Models\Agent";
            $data["transferer_id"] = $agent->id;
            $data["transfered_type"] = "App\Models\Agent";
            $data["transfered_id"] = $request->agent_id;
            $moneyTransfer = MoneyTransfer::create($data);
            
            $agent->wallet = $agent->wallet - $request->value;
            $agent->save();
            
            $transAgent = Agent::find($request->agent_id);
            $transAgent->wallet = $transAgent->wallet + $request->value;
            $transAgent->save();

            $this->saveLogActivity($agent->id, Agent::class, $moneyTransfer->id, MoneyTransfer::class);


            $title = __('new_notification');
            $text = __('money_transferred', [
                'agent' => $agent->name,
                'value' => $value
            ]);

            SaveNotification::create($title, $text, $moneyTransfer->transfered_id, Agent::class, AppNotification::specific);
            SendNotification::send($agent->device_token ?? "", $title, $text);

            return $this->returnResponseSuccessMessage(__('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }
}
