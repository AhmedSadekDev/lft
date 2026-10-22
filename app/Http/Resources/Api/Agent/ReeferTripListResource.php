<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class ReeferTripListResource extends JsonResource
{
    public function toArray($request)
    {
        $first = $this->booking_containers->first();

        return [
            'id' => $this->id,
            'driver_name' => $this->driver?->name ?? '',
            'destination' => $first?->loading?->title ?? '',
        ];
    }
}
