<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Booking;

class LoadingYardResource extends JsonResource
{

    public function toArray($request)
    {
        $agent = auth()->guard('agent')->user();
        
        $cutoff = now()->subHours(24);
        $agent_booking_container_ids = $agent->agent_booking_containers()
            ->wherePivot("superagent_specification_approved", 1)
            ->where(function ($q) use ($cutoff) {
                $q->where(function ($sub) {
                    $sub->where('booking_container_agents.is_in_loading', 1)
                        ->where('booking_container_agents.superagent_loading_approved', 0);
                })->orWhere(function ($q2) use ($cutoff) {
                      $q2->where('booking_container_agents.superagent_loading_approved', 1)
                         ->where(function ($q3) use ($cutoff) {
                             $q3->where('booking_container_agents.loading_approved_at', '>=', $cutoff)
                                ->orWhereNull('booking_container_agents.loading_approved_at')
                                ->orWhere('booking_container_agents.updated_at', '>=', $cutoff);
                         });
                  });
            })
            ->pluck("booking_containers.id")
            ->toArray();
    
        $bookingContainers = $this->bookingContainers()
            ->where('booking_containers.superagent_specification_approved', 1)
            ->where(function ($q) use ($cutoff) {
                $q->where(function ($sub) {
                    $sub->where('booking_containers.is_in_loading', 1)
                        ->where('booking_containers.superagent_loading_approved', 0);
                })->orWhere(function ($q2) use ($cutoff) {
                      $q2->where('booking_containers.superagent_loading_approved', 1)
                         ->where(function ($q3) use ($cutoff) {
                             $q3->where('booking_containers.loading_approved_at', '>=', $cutoff)
                                ->orWhereNull('booking_containers.loading_approved_at')
                                ->orWhere('booking_containers.updated_at', '>=', $cutoff);
                         });
                  });
            })
            ->whereIn("booking_containers.id", $agent_booking_container_ids)
            ->get();

        return [
            "id" => $this->id,
            "title" => $this->title ?? "",
            "booking_containers" =>  BookingContainerResource::collection($bookingContainers)

        ];
    }
}

