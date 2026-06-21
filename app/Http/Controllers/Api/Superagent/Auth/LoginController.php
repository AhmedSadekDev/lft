<?php

namespace App\Http\Controllers\Api\Superagent\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Superagent\Auth\LoginRequest;
use App\Http\Resources\Api\Superagent\SuperagentResource;
use App\Models\Superagent;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginController extends Controller
{
    public function login(LoginRequest $request)
    {
        try {

            $credentials = $request->only('email', 'password');

            // البحث عن المستخدم أولاً للتحقق من وجود session_id قديم
            $superagent = Superagent::where('email', $credentials['email'])->first();

            // إذا كان المستخدم مسجل دخول من قبل، إبطال الـ token القديم
            if ($superagent && $superagent->session_id) {
                try {
                    // محاولة إبطال الـ token القديم باستخدام JWTAuth
                    JWTAuth::setToken($superagent->session_id)->invalidate();
                } catch (\Exception $e) {
                    // في حالة فشل الإبطال (مثل token منتهي)، نتجاهل الخطأ
                }
            }

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
