<?php

namespace App\Http\Resources\Api\Superagent;

use Illuminate\Http\Resources\Json\JsonResource;

class SpecificationBookingResource extends JsonResource
{

    public function toArray($request)
    {
        $superagent = auth()->guard('superagent')->user();

        $superagent_booking_containers = $superagent->superagent_booking_containers()->wherePivot("created_at", ">=", now()->startOfDay())
            ->wherePivot("created_at", "<=", now()->endOfDay())->wherePivot("booking_container_status", 0)->orWherePivot('superagent_specification_approved', 0)->get();

        return [
            "id" => $this->id,
            "booking_number" => $this->booking_number ?? "",
            "is_today" => $superagent_booking_containers->count() ? 1 : 0,
            "booking_containers" => BookingContainerResource::collection($this->bookingContainers()->where("booking_containers.status", 0)->where('booking_containers.superagent_specification_approved', 0)->get())
        ];
    }
}
