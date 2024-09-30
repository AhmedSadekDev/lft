<?php

namespace App\Http\Controllers\Api\Agent\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\Auth\ChangePasswordRequest;
use App\Http\Resources\Api\Agent\AgentResource;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    public function change_password(ChangePasswordRequest $request)
    {
        try {

            $agent = auth()->guard("agent")->user();

            //check if old password and new password if the same

            if (!Hash::check($request->old_password, $agent->password)) {
                return $this->returnError(400, __('auth.old_password_incorrect'));
            }
            $agent->update([

                'password' => $request->new_password,
            ]);

            //response

            return $this->returnAllData(new agentResource($agent), __('alerts.success'));
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            $th;
            return $this->returnError(401, __('auth.credentials_incorrect'));
        }
    }
}
