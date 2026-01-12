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
            "invoice_number" => $this->invoice?->invoice_number ?? "",
            "booking_container_ids" => $this->booking_container_ids ?? [],
            "booking_containers" => BookingContainerResource::collection($this->bookingContainers)
        ];
    }
}
