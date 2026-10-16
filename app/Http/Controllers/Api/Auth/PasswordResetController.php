<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\PasswordResetRequest;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\Auth\VerifyEmailRequest;
use App\Services\LoginService;
use App\Services\PasswordResetService;

class PasswordResetController extends Controller
{
    protected $passwordResetService, $loginService;

    public function __construct(PasswordResetService $passwordResetService, LoginService $loginService)
    {
        $this->passwordResetService = $passwordResetService;
        $this->loginService = $loginService;
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function forgetPassword(PasswordResetRequest $request)
    {
        try {
            $otp = $this->passwordResetService->forgetPassword($request->email);

            return $this->returnAllData($otp, __('auth.email_send'));
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);

            return response()->json(['message' => __('auth.invalid_email')]);
        }
    }

    public function verifyOtp(VerifyEmailRequest $request)
    {
        try {
            $result = $this->passwordResetService->verifyOtp($request);

            return $this->returnAllData([
                'reset_token' => $result['reset_token'],
                'account_type' => $result['account_type'],
            ], $result['message']);
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            if (!$th->getMessage()) {
                return $this->returnError('403', $th->getResponse()?->getData());
            } elseif ($th->getMessage()) {
                return $this->returnError('403', $th->getMessage());
            }
        }
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $this->passwordResetService->resetPassword($request->validated());
            $company = $this->loginService->login($request->only(['email', 'password']));

            return $this->returnAllData($company, __('alerts.updated_successfully'));
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            if (!$th->getMessage()) {
                return $this->returnError('403', $th->getResponse()?->getData());
            } elseif ($th->getMessage()) {
                return $this->returnError('403', $th->getMessage());
            }
        }
    }
}
