<?php
namespace App\Services;

use App\Models\Employee;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request; // تأكد من أنك قمت بإضافة هذا
use Illuminate\Validation\ValidationException;
class LoginService
{
    public function login(array $credentials)
{
    $employee = Employee::where('email', $credentials['email'])->first();

    if ($employee && Hash::check($credentials['password'], $employee->password)) {
        $token = JWTAuth::fromUser($employee);
        $employee->update(['session_id' => $token]);
        return $employee; // رجع الموديل مباشرة
    }

    $company = Company::where('email', $credentials['email'])->first();

    if ($company && Hash::check($credentials['password'], $company->password)) {
        $token = JWTAuth::fromUser($company);
        $company->update(['session_id' => $token]);
        return $company; // رجع الموديل مباشرة
    }

    // throw ValidationException::withMessages([
    //     'email' => [__('auth.failed')],
    // ]);
}

}
