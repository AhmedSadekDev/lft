<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class UnloadingShippingAgentResource extends JsonResource
{

    public function toArray($request)
    {
        $bookingContainers = collect();

        $agent = auth()->guard('agent')->user();

        $cutoff = now()->subHours(24);
        $agent_booking_container_ids = $agent->agent_booking_containers()
            ->wherePivot('superagent_specification_approved', 1)
            ->wherePivot('superagent_loading_approved', 1)
            ->where(function ($q) use ($cutoff) {
                $q->where('booking_container_agents.superagent_unloading_approved', 0)
                  ->orWhere(function ($q2) use ($cutoff) {
                      $q2->where('booking_container_agents.superagent_unloading_approved', 1)
                         ->where(function ($q3) use ($cutoff) {
                             $q3->where('booking_container_agents.unloading_approved_at', '>=', $cutoff)
                                ->orWhereNull('booking_container_agents.unloading_approved_at')
                                ->orWhere('booking_container_agents.updated_at', '>=', $cutoff);
                         });
                  });
            })
            ->pluck("booking_containers.id")
            ->toArray();

        $bookingContainers = $this->bookingContainers()
            ->where('booking_containers.superagent_specification_approved', 1)
            ->where('booking_containers.superagent_loading_approved', 1)
            ->where(function ($q) use ($cutoff) {
                $q->where('booking_containers.superagent_unloading_approved', 0)
                  ->orWhere(function ($q2) use ($cutoff) {
                      $q2->where('booking_containers.superagent_unloading_approved', 1)
                         ->where(function ($q3) use ($cutoff) {
                             $q3->where('booking_containers.unloading_approved_at', '>=', $cutoff)
                                ->orWhereNull('booking_containers.unloading_approved_at')
                                ->orWhere('booking_containers.updated_at', '>=', $cutoff);
                         });
                  });
            })
            ->whereIn("booking_containers.id", $agent_booking_container_ids)
            ->get();

        return [
            "id" => $this->id,
            "title" => $this->title ?? "",
            "booking_containers" => BookingContainerResource::collection($bookingContainers)

        ];
    }
}
