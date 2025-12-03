<?php

namespace App\Http\Resources\Api\Superagent;

use Illuminate\Http\Resources\Json\JsonResource;

class YardBookingResource extends JsonResource
{

    public function toArray($request)
    {
        $superagent = auth()->guard('superagent')->user();
        /** @var \App\Models\Superagent $superagent */

        // Get all booking containers assigned to this superagent (today or all)
        $superagent_booking_containers = $superagent->superagent_booking_containers()
            ->wherePivot("created_at", ">=", now()->startOfDay())
            ->wherePivot("created_at", "<=", now()->endOfDay())
            ->get();

        // If no containers found for today, get all containers assigned to this superagent
        if ($superagent_booking_containers->isEmpty()) {
            $superagent_booking_containers = $superagent->superagent_booking_containers()->get();
        }

        // Get all containers for this booking (show all containers, not just assigned ones)
        // Similar to LoadingYardResource which shows all containers in the yard
        $bookingContainers = $this->bookingContainers()->get();

        return [
            "id" => $this->id,
            "booking_number" => $this->booking_number ?? "",
            "certificate_number" => $this->certificate_number ?? "",
            "shipping_agent" => $this->shipping_agent ?? "",
            "company_name" => $this->company->name ?? "",
            "factory_name" => $this->factory->name ?? "",
            "yard_id" => $this->yard_id ?? "",
            "yard_title" => $this->yard->title ?? "",
            "is_today" => $superagent_booking_containers->count() ? 1 : 0,
            "booking_containers" => BookingContainerResource::collection($bookingContainers)
        ];
    }
}

