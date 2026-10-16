<?php

namespace App\Services;

use App\Http\Resources\Api\Superagent\OtpResource;
use App\Models\OTP;
use App\Models\Superagent;
use App\Notifications\Api\Auth\ResetPassword;
use App\Traits\GenerateOtpSuperagentTrait;
use App\Traits\IssuesPasswordResetToken;
use Carbon\Carbon;

class PasswordResetSuperagentService
{
    use GenerateOtpSuperagentTrait;
    use IssuesPasswordResetToken;

    public function sendOtp($email)
    {
        if ($email) {
            $otp = $this->generateOtp($email);
            $otp->superagent->notify(new ResetPassword($otp->otp));

            return new OtpResource($otp);
        }

        abort(404, __('auth.invalid_email'));
    }

    public function verifyOtp($request): array
    {
        $superagent = Superagent::whereEmail($request->email)->first();

        if (!$superagent) {
            abort(404, __('alerts.failed'));
        }

        $verificationCode = OTP::where('superagent_id', $superagent->id)
            ->where('otp', $request->otp)
            ->latest('id')
            ->first();
        $now = Carbon::now();

        if ($verificationCode && $now->isAfter($verificationCode->expire_at)) {
            abort(404, __('auth.expired_otp'));
        }

        if ($verificationCode) {
            $resetToken = $this->issueResetToken($verificationCode);

            return [
                'message' => __('auth.verified'),
                'reset_token' => $resetToken,
                'account_type' => self::ACCOUNT_SUPERAGENT,
            ];
        }

        abort(404, __('alerts.failed'));
    }

    public function resetPassword($request)
    {
        $email = is_array($request) ? ($request['email'] ?? null) : $request->email;
        $password = is_array($request) ? ($request['password'] ?? null) : $request->password;
        $resetToken = is_array($request) ? ($request['reset_token'] ?? null) : $request->reset_token;

        if (!$resetToken) {
            abort(403, __('auth.invalid_reset_token'));
        }

        return $this->consumeResetToken(
            $resetToken,
            $email,
            $password,
            self::ACCOUNT_SUPERAGENT,
            Superagent::class
        );
    }
}
