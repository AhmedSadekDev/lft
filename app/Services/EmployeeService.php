<?php
namespace App\Services;


Class EmployeeService{

    public function update($request){
        $company = auth('employees')->user();
        $company->update($request);
        return $company;
    }
}
