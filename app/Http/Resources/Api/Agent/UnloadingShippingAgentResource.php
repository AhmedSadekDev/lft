<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class UnloadingShippingAgentResource extends JsonResource
{

    public function toArray($request)
    {
        $bookingContainers = collect();

        $agent = auth()->guard('agent')->user();

        $agent_booking_container_ids = $agent->agent_booking_containers()->wherePivot("created_at", ">=", now()->startOfDay())
            ->wherePivot("created_at", "<=", now()->endOfDay())->wherePivot("superagent_unloading_approved", 0)->wherePivot('superagent_loading_approved', 1)->wherePivot('superagent_specification_approved', 1)->get()->pluck("id")->toArray();
            
        $bookingContainers = $this->bookingContainers()->whereIn("booking_containers.id", $agent_booking_container_ids)->get();

        return [
            "id" => $this->id,
            "title" => $this->title ?? "",
            "booking_containers" => BookingContainerResource::collection($bookingContainers)

        ];
    }
}
