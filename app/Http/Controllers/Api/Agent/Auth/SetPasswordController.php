<?php

namespace App\Http\Controllers\Api\Agent\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\Auth\SetPasswordRequest;
use App\Http\Resources\Api\Agent\AgentResource;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SetPasswordController extends Controller
{
    public function set_password(SetPasswordRequest $request)
    {

        $token = $request->token;
        $agent = Agent::where('session_id', $token)->first();


        if (!$agent) {

            return $this->returnError(404, __('alerts.no_data_found'));
        }

        $agent->update(["password" => $request->password]);
        //response

        return $this->returnAllData(new AgentResource($agent), __('alerts.success'));
    }
}
