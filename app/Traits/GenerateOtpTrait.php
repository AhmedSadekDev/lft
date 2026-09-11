<?php
namespace App\Traits;

use App\Aggregators\OTPAggregator;
use App\Models\Company;
use App\Models\Employee;
use App\Models\OTP;
use Carbon\Carbon;

trait GenerateOtpTrait{

    public function generateOtp($email)
    {
        // نبحث عن الشركة أولاً
        $company = Company::whereEmail($email)->first();

        if ($company) {
            // إذا كانت الشركة موجودة
            $verificationCode = OTP::where('company_id', $company->id)->latest()->first();

            $now = Carbon::now();

            if ($verificationCode && $now->isBefore($verificationCode->expire_at)) {
                return $verificationCode; // إذا كان الـ OTP موجودًا وصالحًا
            }

            // إذا لم يكن هناك OTP صالح، نقوم بإنشاء أو تحديث OTP جديد
            $companyOtp = OTP::where('company_id', $company->id)->first();

            if ($companyOtp) {
                // تحديث OTP موجود
                $companyOtp->update([
                    'otp' => OTPAggregator::generateOTP(),
                    'expire_at' => Carbon::now()->addMinutes(10)
                ]);
                return $companyOtp;
            } else {
                // إنشاء OTP جديد
                $otp = OTP::create([
                    'company_id' => $company->id,
                    'otp' => OTPAggregator::generateOTP(),
                    'expire_at' => Carbon::now()->addMinutes(10)
                ]);
                return $otp;
            }
        }

        // إذا لم تكن الشركة موجودة، نبحث عن الموظف
        $employee = Employee::whereEmail($email)->first();

        if ($employee) {
            // إذا كان الموظف موجودًا
            $verificationCode = OTP::where('employee_id', $employee->id)->latest()->first();

            $now = Carbon::now();

            if ($verificationCode && $now->isBefore($verificationCode->expire_at)) {
                return $verificationCode; // إذا كان الـ OTP موجودًا وصالحًا
            }

            // إذا لم يكن هناك OTP صالح، نقوم بإنشاء أو تحديث OTP جديد
            $employeeOtp = OTP::where('employee_id', $employee->id)->first();

            if ($employeeOtp) {
                // تحديث OTP موجود
                $employeeOtp->update([
                    'otp' => OTPAggregator::generateOTP(),
                    'expire_at' => Carbon::now()->addMinutes(10)
                ]);
                return $employeeOtp;
            } else {
                // إنشاء OTP جديد
                $otp = OTP::create([
                    'employee_id' => $employee->id,
                    'otp' => OTPAggregator::generateOTP(),
                    'expire_at' => Carbon::now()->addMinutes(10)
                ]);
                return $otp;
            }
        }

        // إذا لم يكن هناك شركة أو موظف بهذا البريد الإلكتروني، نرجع خطأ
        return response()->json(['error' => 'User not found'], 404);
    }
}
