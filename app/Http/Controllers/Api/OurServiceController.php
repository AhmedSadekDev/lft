<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ServicesResource;
use App\Models\OurService;
use Illuminate\Http\Request;

class OurServiceController extends Controller
{

    public function getServices(){
        $data = ServicesResource::collection(OurService::all());
        return $this->returnAllData($data);
    }
}
