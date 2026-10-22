<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
  
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "name" => $this->name ?? "",
            "phone" => $this->phone ?? "",
            "national_id" => $this->national_id ?? "",
            "passport_number" => $this->passport_number ?? "",
        ];
    }
}
