<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Agent\CarResource;
use App\Http\Resources\Api\Agent\YardResource;
use App\Models\Yard;
use Illuminate\Http\Request;

class YardController extends Controller
{
    public function fetch_yards()
    {
        try {

            $yards = Yard::get();
        
            $data = YardResource::collection($yards);
        

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(500, $Exception->getMessage());
        }
    }
}
