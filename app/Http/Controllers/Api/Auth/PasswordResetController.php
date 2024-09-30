<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Requests\Api\Auth\PasswordResetRequest;
use App\Services\PasswordResetService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\Auth\VerifyEmailRequest;
use App\Services\loginService;

class PasswordResetController extends Controller
{
    protected $passwordResetService, $loginService;

    public function __construct(PasswordResetService $passwordResetService, loginService $loginService){
        $this->passwordResetService = $passwordResetService;
        $this->loginService = $loginService;
    }

    /**
    *   Handle an incoming password reset link request.
    *
    *   @param  \Illuminate\Http\Request  $request
    *   @return \Illuminate\Http\JsonResponse
    *
    *   @throws \Illuminate\Validation\ValidationException
    */
    public function forgetPassword(PasswordResetRequest $request)
    {
        try {
            //code...
            $otp = $this->passwordResetService->forgetPassword($request->email);
            return $this->returnAllData($otp, __('auth.email_send'));
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th); $th;
            return response()->json(['message' => __('auth.invalid_email')]);
        }
    }

    public function verifyOtp(VerifyEmailRequest $request)
    {
        try {
            //code...
            $res = $this->passwordResetService->verifyOtp($request);
            return $this->returnResponseSuccessMessage($res->getData()->message);
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th); $th;
            if(!$th->getMessage()){
                return $this->returnError('403', $th->getResponse()?->getData());
            }elseif($th->getMessage()){
                return $this->returnError('403', $th->getMessage());
            }
        }
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            //code...
            $this->passwordResetService->resetPassword($request->validated());
            $company = $this->loginService->login($request->validated());
            return $this->returnAllData($company, __('alerts.updated_successfully'));
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th); $th;
            if(!$th->getMessage()){
                return $this->returnError('403', $th->getResponse()?->getData());
            }elseif($th->getMessage()){
                return $this->returnError('403', $th->getMessage());
            }
        }
    }
}
