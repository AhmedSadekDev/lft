<?php

namespace App\Http\Controllers\Api\Superagent\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Superagent\Auth\LoginRequest;
use App\Http\Resources\Api\Superagent\SuperagentResource;
use App\Models\Superagent;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function login(LoginRequest $request)
    {
        try {

            $credentials = $request->only('email', 'password');



            $token = auth()->guard('superagent')->attempt($credentials);


            //check token is exist or now
            if ($token) {

                $superagent = auth()->guard('superagent')->user();


                $superagent->update(['session_id' => $token,
                    'device_token' => $request->device_token ?? ""]);


                return $this->returnAllData(new SuperagentResource($superagent), __('alerts.success'));
            } else {
                return $this->returnError(401, __('auth.credentials_incorrect'));
            }
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            $th;
            return $this->returnError(401, __('auth.credentials_incorrect'));
        }
    }
    public function logout() {
        auth()->guard("superagent")->logout();
        return $this->returnSuccessMessage( __('alerts.success'));

    }
}
