<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class CarResource extends JsonResource
{
  
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "car_number" => $this->car_number ?? ""
                ];
    }
}
