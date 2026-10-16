<?php

namespace App\Traits;

use App\Aggregators\OTPAggregator;
use App\Models\Agent;
use App\Models\OTP;
use Carbon\Carbon;

trait GenerateOtpAgentTrait
{
    abstract protected function clearResetTokenFields(): array;

    public function generateOtp($email)
    {
        $agent = Agent::whereEmail($email)->first();

        # User Does not Have Any Existing OTP
        $verificationCode = OTP::where('agent_id', $agent->id)->latest()->first();

        $now = Carbon::now();

        if ($verificationCode && $now->isBefore($verificationCode->expire_at)) {
            return $verificationCode;
        }

        // Create a New OTP
        $agentOtp = OTP::whereAgentId($agent->id)->first();
        if ($agentOtp) {
            $agentOtp->update(array_merge([
                'otp' => OTPAggregator::generateOTP(),
                'expire_at' => Carbon::now()->addMinutes(10),
            ], $this->clearResetTokenFields()));
            return $agentOtp;
        }

        return OTP::create([
            'agent_id' => $agent->id,
            'otp' => OTPAggregator::generateOTP(),
            'expire_at' => Carbon::now()->addMinutes(10),
        ]);
    }
}
