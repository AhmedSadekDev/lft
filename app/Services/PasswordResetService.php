<?php

namespace App\Services;

use App\Http\Resources\Api\OtpResource;
use App\Models\Company;
use App\Models\Employee;
use App\Models\OTP;
use App\Notifications\Api\Auth\EmployeeResetPassword;
use App\Notifications\Api\Auth\ResetPassword;
use App\Traits\GenerateOtpTrait;
use App\Traits\IssuesPasswordResetToken;
use Carbon\Carbon;

class PasswordResetService
{
    use GenerateOtpTrait;
    use IssuesPasswordResetToken;

    public function forgetPassword($email)
    {
        if ($email) {
            $otp = $this->generateOtp($email);
            if (!$otp instanceof OTP) {
                abort(404, __('auth.invalid_email'));
            }

            if ($otp->employee_id) {
                $otp->employee->notify(new EmployeeResetPassword($otp->otp));
            } else {
                $otp->company->notify(new ResetPassword($otp->otp));
            }

            return new OtpResource($otp);
        }

        abort(404, __('auth.invalid_email'));
    }

    public function verifyOtp($request): array
    {
        $email = $request->email;
        $now = Carbon::now();

        // Same preference as GenerateOtpTrait: company before employee when emails collide.
        $company = Company::where('email', $email)->first();
        if ($company) {
            $verificationCode = OTP::where('company_id', $company->id)
                ->where('otp', $request->otp)
                ->latest('id')
                ->first();

            if ($verificationCode) {
                if ($now->isAfter($verificationCode->expire_at)) {
                    abort(404, __('auth.expired_otp'));
                }

                $resetToken = $this->issueResetToken($verificationCode);

                return [
                    'message' => __('auth.verified'),
                    'reset_token' => $resetToken,
                    'account_type' => self::ACCOUNT_COMPANY,
                ];
            }
        }

        $employee = Employee::where('email', $email)->first();
        if ($employee) {
            $verificationCode = OTP::where('employee_id', $employee->id)
                ->where('otp', $request->otp)
                ->latest('id')
                ->first();

            if ($verificationCode) {
                if ($now->isAfter($verificationCode->expire_at)) {
                    abort(404, __('auth.expired_otp'));
                }

                $resetToken = $this->issueResetToken($verificationCode);

                return [
                    'message' => __('auth.verified'),
                    'reset_token' => $resetToken,
                    'account_type' => self::ACCOUNT_EMPLOYEE,
                ];
            }
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

        // Bind by account type using the same company-first rule as OTP generation.
        $company = Company::where('email', $email)->first();
        if ($company) {
            return $this->consumeResetToken(
                $resetToken,
                $email,
                $password,
                self::ACCOUNT_COMPANY,
                Company::class
            );
        }

        $employee = Employee::where('email', $email)->first();
        if ($employee) {
            return $this->consumeResetToken(
                $resetToken,
                $email,
                $password,
                self::ACCOUNT_EMPLOYEE,
                Employee::class
            );
        }

        abort(404, __('auth.user_not_found'));
    }
}
