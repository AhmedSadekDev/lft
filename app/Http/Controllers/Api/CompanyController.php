<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CompanyRequest;
use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyController  extends Controller
{
    protected $companyService ;

    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    public function update(CompanyRequest $request){
        DB::beginTransaction();
        try {
            //code...
            $company= $this->companyService->update($request->validated());
            DB::commit();
            return $this->returnAllData($company, __('alerts.updated_successfully'));
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th); $th;
            DB::rollBack();
            if(!$th->getMessage()){
                return $this->returnError('403', $th->getResponse()?->getData());
            }elseif($th->getMessage()){
                return $this->returnError('403', $th->getMessage());
            }
        }

    }
}
