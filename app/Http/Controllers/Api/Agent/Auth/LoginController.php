<?php

namespace App\Http\Controllers\Api\Agent\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\Auth\LoginRequest;
use App\Http\Resources\Api\Agent\AgentResource;
use App\Models\Agent;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        try {

            $credentials = $request->only('email', 'password');



            $token = auth()->guard('agent')->attempt($credentials);


            //check token is exist or now
            if ($token) {

                $agent = auth()->guard('agent')->user();


                $agent->update([
                    'session_id' => $token,
                    'device_token' => $request->device_token ?? ""
                ]);


                return $this->returnAllData(new AgentResource($agent), __('alerts.success'));
            } else {
                return $this->returnError(401, __('auth.credentials_incorrect'));
            }
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            $th;
            return $this->returnError(401, __('auth.credentials_incorrect'));
        }
    }
    public function logout()
    {
        auth()->guard("agent")->logout();
        return $this->returnSuccessMessage(__('alerts.success'));
    }
}
