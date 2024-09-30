<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class NameResource extends JsonResource
{
  
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "name" => $this->name ?? ""
        ];
    }
}
