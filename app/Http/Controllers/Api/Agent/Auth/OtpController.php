<?php

namespace App\Http\Controllers\Api\Agent\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\Auth\EmailRequest;
use App\Http\Requests\Api\Agent\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\Agent\Auth\VerifyEmailRequest;
use App\Http\Resources\Api\Agent\AgentResource;
use App\Services\PasswordResetAgentService;

class OtpController extends Controller
{
    protected $passwordResetAgentService;

    public function __construct(PasswordResetAgentService $passwordResetAgentService)
    {
        $this->passwordResetAgentService = $passwordResetAgentService;
    }

    /**
     *   Handle an incoming password reset link request.
     *
     *   @param  \Illuminate\Http\Request  $request
     *   @return \Illuminate\Http\JsonResponse
     *
     *   @throws \Illuminate\Validation\ValidationException
     */
    public function sendOtp(EmailRequest $request)
    {
        try {
            //code...
            $otp = $this->passwordResetAgentService->sendOtp($request->email);
            return $this->returnAllData($otp, __('auth.email_send'));
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            $th;
            return response()->json(['message' => __('auth.invalid_email')]);
        }
    }

    public function verifyOtp(VerifyEmailRequest $request)
    {
        try {
            //code...
            $res = $this->passwordResetAgentService->verifyOtp($request);
            return $this->returnResponseSuccessMessage($res->getData()->message);
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            $th;
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
            //code...
            $agent = $this->passwordResetAgentService->resetPassword($request);
            return $this->returnAllData(new AgentResource($agent), __('alerts.updated_successfully'));
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            $th;
            if (!$th->getMessage()) {
                return $this->returnError('403', $th->getResponse()?->getData());
            } elseif ($th->getMessage()) {
                return $this->returnError('403', $th->getMessage());
            }
        }
    }
}
