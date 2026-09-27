<?php

namespace App\Http\Resources\Api\Superagent;

use Illuminate\Http\Resources\Json\JsonResource;

class MissionBookingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->id,
            'booking_number' => $this->booking_number ?? '',
            'booking_containers' => allBookingContainerResource::collection($this->bookingContainers),
        ];
    }
}
