<?php
namespace App\Services;

use App\Http\Resources\Api\OtpResource;
use App\Models\Company;
use App\Notifications\Api\Auth\ResetPassword;
use App\Notifications\Api\Auth\EmployeeResetPassword;
use App\Traits\GenerateOtpTrait;
use App\Models\Employee;
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
            if($otp->employee_id){
                $otp->employee->notify(new EmployeeResetPassword($otp->otp));
            } else{
                $otp->company->notify(new ResetPassword($otp->otp));
            }
            return new OtpResource($otp);
        }

        abort(response()->json(__('auth.invalid_email'), 404));
    }

    public function verifyOtp($request)
{
    $now = Carbon::now();

    // التحقق إذا كان OTP يخص شركة
    $verificationCode = OTP::where('company_id', $request->company_id)
        ->where('otp', $request->otp)
        ->first();

    if ($verificationCode) {
        if ($now->isAfter($verificationCode->expire_at)) {
            abort(response()->json(__('auth.expired_otp'), 404));
        }

        $company = Company::find($request->company_id);
        if ($company) {
            $verificationCode->update([
                'expire_at' => $now,
            ]);
            return response()->json(['message' => __('auth.verified')], 200);
        }
    }

    // التحقق إذا كان OTP يخص موظف
    $verificationCodeEmployee = OTP::where('employee_id', $request->company_id)
        ->where('otp', $request->otp)
        ->first();

    if ($verificationCodeEmployee) {
        if ($now->isAfter($verificationCodeEmployee->expire_at)) {
            abort(response()->json(__('auth.expired_otp'), 404));
        }

        $employee = Employee::find($request->company_id);
        if ($employee) {
            $verificationCodeEmployee->update([
                'expire_at' => $now,
            ]);
            return response()->json(['message' => __('auth.verified')], 200);
        }
    }

    // لم يتم التحقق
    abort(response()->json(__('alerts.failed'), 404));
}


    public function resetPassword($request)
{
    // أولاً نحاول نلاقي شركة بالإيميل
    $company = Company::where('email', $request['email'])->first();

    if ($company) {
        $company->update(['password' => $request['password']]);
        return response()->json(['message' => __('auth.password_updated')], 200);
    }

    // لو ملقيناش شركة، نجرب نلاقي موظف
    $employee = Employee::where('email', $request['email'])->first();

    if ($employee) {
        $employee->update(['password' => $request['password']]);
        return response()->json(['message' => __('auth.password_updated')], 200);
    }

    // لو لا شركة ولا موظف موجودين
    return response()->json(['message' => __('auth.user_not_found')], 404);
}


}
