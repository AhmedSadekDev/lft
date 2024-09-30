<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\SetPasswordRequest;
use App\Http\Resources\Api\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class SetPasswordController extends Controller
{
    public function setPassword(SetPasswordRequest $request)
    {
        $token = $request->token;
        $company = Company::where('session_id', $token)->first();

        if(!is_null($company)){
            $company->update($request->only('password'));
            return $this->returnAllData(new CompanyResource($company), __('alerts.success'));
        }else{
            return $this->returnError(400, __('alerts.no_data_found'));
        }

    }
}
