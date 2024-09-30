<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SponserResource;
use App\Models\Sponser;
use Illuminate\Http\Request;

class SponserController extends Controller
{
    public function getSponsers(){
        $data = SponserResource::collection(Sponser::all());
        return $this->returnAllData($data);
    }
}
