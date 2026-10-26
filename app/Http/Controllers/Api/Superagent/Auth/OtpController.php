<?php

namespace App\Http\Controllers\Api\Superagent\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Superagent\Auth\EmailRequest;
use App\Http\Requests\Api\Superagent\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\Superagent\Auth\VerifyEmailRequest;
use App\Http\Resources\Api\Superagent\SuperagentResource;
use App\Services\PasswordResetSuperagentService;

class OtpController extends Controller
{
    protected $passwordResetSuperagentService;

    public function __construct(PasswordResetSuperagentService $passwordResetSuperagentService)
    {
        $this->passwordResetSuperagentService = $passwordResetSuperagentService;
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function sendOtp(EmailRequest $request)
    {
        try {
            $otp = $this->passwordResetSuperagentService->sendOtp($request->email);

            return $this->returnAllData($otp, __('auth.email_send'));
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);

            return response()->json(['message' => __('auth.invalid_email')]);
        }
    }

    public function verifyOtp(VerifyEmailRequest $request)
    {
        try {
            $result = $this->passwordResetSuperagentService->verifyOtp($request);

            return $this->returnAllData([
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
            $superagent = $this->passwordResetSuperagentService->resetPassword($request);

            return $this->returnAllData(new SuperagentResource($superagent), __('alerts.updated_successfully'));
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
