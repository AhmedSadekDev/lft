<?php
namespace App\Http\Controllers\Api\Desktop\Orders;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Desktop\CompanyResource;
use App\Models\CompanyFatoorah;

class CompanyController extends Controller
{
    public function all()
    {
        $companies = CompanyFatoorah::get();
        $companies = CompanyResource::collection($companies);
        return $this->returnData("data", ["compaines" => $companies], 'تم استرجاع الداتا');
    }
}
