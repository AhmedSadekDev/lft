<?php

namespace App\Http\Resources\Api\Superagent;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "name" => $this->name ?? "",
            "image" => $this->image ?? "",
            "number_of_bookings" => $this->number_of_bookings ?? 0
        ];
    }
}
