<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class SpecificationBookingResource extends JsonResource
{

    public function toArray($request)
    {
        $containers = ($this->bookingContainers ?? collect())->values();

        return [
            "id" => $this->id,
            "booking_number" => $this->booking_number ?? "",
            "booking_containers" => $containers->map(
                fn ($container) => (new BookingContainerResource($container))->forStage(0)
            )->values(),
        ];
    }
}
