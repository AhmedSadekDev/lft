<?php

namespace App\Traits;

use App\Aggregators\OTPAggregator;
use App\Models\Company;
use App\Models\OTP;
use App\Models\User;
use Carbon\Carbon;

trait GenerateOtpTrait{

    public function generateOtp($email)
    {
        $company = Company::whereEmail($email)->first();

        # User Does not Have Any Existing OTP
        $verificationCode = OTP::where('company_id', $company->id)->latest()->first();

        $now = Carbon::now();

        if($verificationCode && $now->isBefore($verificationCode->expire_at)){
            return $verificationCode;
        }

        // Create a New OTP
        $companyOtp = OTP::whereCompanyId($company->id)->first();
        if($companyOtp){
            $companyOtp->update([
                'otp' => OTPAggregator::generateOTP(),
                'expire_at' => Carbon::now()->addMinutes(10)
            ]);
            return $companyOtp;
        }else{
            return OTP::create([
                'company_id' => $company->id,
                'otp' => OTPAggregator::generateOTP(),
                'expire_at' => Carbon::now()->addMinutes(10)
            ]);
        }

    }
}
