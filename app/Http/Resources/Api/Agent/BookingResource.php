<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "booking_number" => $this->booking_number ?? "",
            "booking_containers" => BookingContainerResource::collection($this->bookingContainers)
        ];
    }
}
