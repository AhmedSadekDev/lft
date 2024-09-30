<?php
namespace App\Services;

use App\Http\Resources\Api\OtpResource;
use App\Models\Company;
use App\Notifications\Api\Auth\ResetPassword;
use App\Traits\GenerateOtpTrait;
use App\Models\OTP;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

Class PasswordResetService{

    use GenerateOtpTrait;

    public function forgetPassword($email)
    {
        // We will send the password reset link to this company. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the company. Finally, we'll send out a proper response.
        if ($email) {
            $otp = $this->generateOtp($email);
            $otp->company->notify(new ResetPassword($otp->otp));
            return new OtpResource($otp);
        }

        abort(response()->json(__('auth.invalid_email'), 404));
    }

    public function verifyOtp($request)
    {
        $verificationCode = OTP::where('company_id', $request->company_id)->where('otp', $request->otp)->first();
        $now = Carbon::now();
        if($verificationCode && $now->isAfter($verificationCode->expire_at)){
            abort(response()->json(__('auth.expired_otp'), 404));
        }

        $company = Company::whereId($request->company_id)->first();

        if($company){
            // Expire The OTP
            $verificationCode->update([
                'expire_at' => Carbon::now()
            ]);

            return response()->json([ 'message' => __('auth.verified')], 200);
        }
        abort(response()->json(__('alerts.failed'), 404));
    }

    public function resetPassword($request){
        $company = Company::whereEmail($request['email'])->first();
        $company->update(['password' => $request['password']]);
        return $company;
    }

}
