<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class CarExpenseResource extends JsonResource
{
  
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "title" => $this->title ?? "",
            "text" => $this->notes ?? "",
            "date" => $this->created_at ?? "",
            "value" => $this->value
        ];
    }
}
