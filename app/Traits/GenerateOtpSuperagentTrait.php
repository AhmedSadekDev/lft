<?php

namespace App\Traits;

use App\Aggregators\OTPAggregator;
use App\Models\OTP;
use App\Models\Superagent;

use Carbon\Carbon;

trait GenerateOtpSuperagentTrait{

    public function generateOtp($email)
    {
        $superagent = Superagent::whereEmail($email)->first();

        # User Does not Have Any Existing OTP
        $verificationCode = OTP::where('superagent_id', $superagent->id)->latest()->first();

        $now = Carbon::now();

        if($verificationCode && $now->isBefore($verificationCode->expire_at)){
            return $verificationCode;
        }

        // Create a New OTP
        $superagentOtp = OTP::whereAgentId($superagent->id)->first();
        if($superagentOtp){
            $superagentOtp->update([
                'otp' => OTPAggregator::generateOTP(),
                'superagent_id' => $superagent->id,
                'expire_at' => Carbon::now()->addMinutes(10)
            ]);
            return $superagentOtp;
        }else{
            return OTP::create([
                'superagent_id' => $superagent->id,
                'otp' => OTPAggregator::generateOTP(),
                'expire_at' => Carbon::now()->addMinutes(10)
            ]);
        }

    }
}
