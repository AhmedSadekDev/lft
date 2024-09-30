<?php

namespace App\Http\Controllers\Api\Agent;

use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use App\Models\CitiesAndRegions;
use App\Http\Controllers\Controller;

class CityController extends Controller
{
    use ResponseTrait;
    
    public function index()
    {
        $cities = CitiesAndRegions::all()->map(function($city) {
            return [
                'id' => $city->id,
                'title' => $city->title,
            ];
        });

        return $this->returnAllData($cities, __('alerts.success'));
    }
}
