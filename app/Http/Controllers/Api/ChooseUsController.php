<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ChooseResource;
use App\Models\ChooseUs;
use Illuminate\Http\Request;

class ChooseUsController extends Controller
{
    public function getChooses(){
        $data = ChooseResource::collection(ChooseUs::all());
        return $this->returnAllData($data);
    }
}
