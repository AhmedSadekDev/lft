<?php
namespace App\Services;


Class CompanyService{

    public function update($request){
        $company = auth()->user();
        $company->update($request);
        return $company;
    }
}
