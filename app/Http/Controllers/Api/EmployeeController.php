<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function getEmployees(){
        $employees = auth()->user()->employees;
        return $this->returnAllData($employees);
    }
}
