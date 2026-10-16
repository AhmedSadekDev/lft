<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class EmployeeController extends Controller
{
    public function getEmployees()
    {
        if (auth('employees')->check()) {
            return $this->returnError(403, __('alerts.notAuth'));
        }

        $company = auth('api')->user();
        if (!$company) {
            return $this->returnError(401, __('alerts.notAuth'));
        }

        return $this->returnAllData($company->employees);
    }
}
