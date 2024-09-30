<?php
namespace App\Services;

use App\Http\Resources\Api\Agent\OtpResource;
use App\Models\Agent;
use App\Notifications\Api\Auth\ResetPassword;
use App\Models\OTP;
use App\Traits\GenerateOtpAgentTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

Class PasswordResetAgentService{

    use GenerateOtpAgentTrait;

    public function sendOtp($email)
    {
        // We will send the password reset link to this agent. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the agent. Finally, we'll send out a proper response.
        if ($email) {
            $otp = $this->generateOtp($email);
            $otp->agent->notify(new ResetPassword($otp->otp));
            return new OtpResource($otp);
        }

        abort(response()->json(__('auth.invalid_email'), 404));
    }

    public function verifyOtp($request)
    {
        $agent = Agent::whereEmail($request->email)->first();

        $verificationCode = OTP::where('agent_id', $agent->id)->where('otp', $request->otp)->first();
        $now = Carbon::now();

        if($verificationCode && $now->isAfter($verificationCode->expire_at)){
            abort(response()->json(__('auth.expired_otp'), 404));
        }


        if($agent && $verificationCode){
            // Expire The OTP
            $verificationCode->update([
                'expire_at' => Carbon::now()
            ]);

            return response()->json([ 'message' => __('auth.verified')], 200);
        }
        abort(response()->json(__('alerts.failed'), 404));
    }

    public function resetPassword($request){
        $agent = Agent::whereEmail($request->email)->first();
        $agent->update(['password' => $request['password']]);
        return $agent;
    }

}
