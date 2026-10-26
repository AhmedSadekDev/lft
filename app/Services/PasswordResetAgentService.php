<?php

namespace App\Services;

use App\Http\Resources\Api\Agent\OtpResource;
use App\Models\Agent;
use App\Models\OTP;
use App\Notifications\Api\Auth\ResetPassword;
use App\Traits\GenerateOtpAgentTrait;
use App\Traits\IssuesPasswordResetToken;
use Carbon\Carbon;

class PasswordResetAgentService
{
    use GenerateOtpAgentTrait;
    use IssuesPasswordResetToken;

    public function sendOtp($email)
    {
        if ($email) {
            $otp = $this->generateOtp($email);
            $otp->agent->notify(new ResetPassword($otp->otp));

            return new OtpResource($otp);
        }

        abort(404, __('auth.invalid_email'));
    }

    /**
     * Validate OTP and open a short server-side reset window (no reset_token returned).
     */
    public function verifyOtp($request): array
    {
        $agent = Agent::whereEmail($request->email)->first();

        if (! $agent) {
            abort(404, __('alerts.failed'));
        }

        $verificationCode = OTP::where('agent_id', $agent->id)
            ->where('otp', $request->otp)
            ->latest('id')
            ->first();
        $now = Carbon::now();

        if ($verificationCode && $now->isAfter($verificationCode->expire_at)) {
            abort(404, __('auth.expired_otp'));
        }

        if ($verificationCode) {
            $this->markOtpVerifiedForPasswordReset($verificationCode);

            return [
                'message' => __('auth.verified'),
                'account_type' => self::ACCOUNT_AGENT,
            ];
        }

        abort(404, __('alerts.failed'));
    }

    public function resetPassword($request)
    {
        $email = is_array($request) ? ($request['email'] ?? null) : $request->email;
        $password = is_array($request) ? ($request['password'] ?? null) : $request->password;

        if (! $email || ! $password) {
            abort(422, __('alerts.failed'));
        }

        return $this->consumeVerifiedOtpPasswordReset(
            $email,
            $password,
            self::ACCOUNT_AGENT,
            Agent::class
        );
    }
}
