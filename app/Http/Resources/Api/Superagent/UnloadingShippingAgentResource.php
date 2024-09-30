<?php

namespace App\Http\Resources\Api\Superagent;

use Illuminate\Http\Resources\Json\JsonResource;

class UnloadingShippingAgentResource extends JsonResource
{

    public function toArray($request)
    {
        $superagent = auth()->guard('superagent')->user();
        $superagent_booking_containers = $superagent->superagent_booking_containers()->wherePivot("created_at", ">=", now()->startOfDay())
            ->wherePivot("created_at", "<=", now()->endOfDay())
            //->wherePivot("booking_container_status", 2)
            ->get();
        $bookingContainers = $this->bookingContainers()->where('superagent_loading_approved', 1)->where('superagent_unloading_approved', 0)->where('superagent_specification_approved', 1)->get();

        return [
            "id" => $this->id,
            "title" => $this->title ?? "",
            "booking_containers" =>  BookingContainerResource::collection($bookingContainers)

        ];
    }
}
