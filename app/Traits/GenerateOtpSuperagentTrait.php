<?php

namespace App\Traits;

use App\Aggregators\OTPAggregator;
use App\Models\OTP;
use App\Models\Superagent;

use Carbon\Carbon;

trait GenerateOtpSuperagentTrait
{
    abstract protected function clearResetTokenFields(): array;

    public function generateOtp($email)
    {
        $superagent = Superagent::whereEmail($email)->first();

        # User Does not Have Any Existing OTP
        $verificationCode = OTP::where('superagent_id', $superagent->id)->latest()->first();

        $now = Carbon::now();

        if ($verificationCode && $now->isBefore($verificationCode->expire_at)) {
            return $verificationCode;
        }

        // Create a New OTP (fix: was incorrectly querying whereAgentId)
        $superagentOtp = OTP::where('superagent_id', $superagent->id)->first();
        if ($superagentOtp) {
            $superagentOtp->update(array_merge([
                'otp' => OTPAggregator::generateOTP(),
                'superagent_id' => $superagent->id,
                'expire_at' => Carbon::now()->addMinutes(10),
            ], $this->clearResetTokenFields()));
            return $superagentOtp;
        }

        return OTP::create([
            'superagent_id' => $superagent->id,
            'otp' => OTPAggregator::generateOTP(),
            'expire_at' => Carbon::now()->addMinutes(10),
        ]);
    }
}
