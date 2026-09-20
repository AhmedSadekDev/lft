<?php

namespace App\Http\Resources\Api\Agent;

use App\Http\Resources\Api\Superagent\BookingResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SpecificationShippingAgentResource extends JsonResource
{

    public function toArray($request)
    {

        $cutoff = now()->subHours(24);
        $agentAssignment = $request->user()->agent_booking_containers()->wherePivot('stage_type', 0)
            ->whereDoesntHave('booking.invoice')
            ->where(function ($q) use ($cutoff) {
                $q->where('booking_container_agents.superagent_specification_approved', 0)
                  ->orWhere(function ($q2) use ($cutoff) {
                      $q2->where('booking_container_agents.superagent_specification_approved', 1)
                         ->where(function ($q3) use ($cutoff) {
                             $q3->where('booking_container_agents.specification_approved_at', '>=', $cutoff)
                                ->orWhereNull('booking_container_agents.specification_approved_at')
                                ->orWhere('booking_container_agents.updated_at', '>=', $cutoff);
                         });
                  });
            })
            ->pluck('booking_containers.id')
            ->toArray();

        return [
            "id" => $this->id,
            "title" => $this->title ?? "",
            "bookings" =>  SpecificationBookingResource::collection(
                $this->bookings()->whereHas("bookingContainers", function ($q) use ($agentAssignment, $cutoff) {
                    $q->where(function ($query) use ($cutoff) {
                        $query->where("booking_containers.superagent_specification_approved", 0)
                              ->orWhere(function ($q2) use ($cutoff) {
                                  $q2->where("booking_containers.superagent_specification_approved", 1)
                                     ->where(function ($q3) use ($cutoff) {
                                         $q3->where("booking_containers.specification_approved_at", ">=", $cutoff)
                                            ->orWhereNull("booking_containers.specification_approved_at")
                                            ->orWhere("booking_containers.updated_at", ">=", $cutoff);
                                     });
                              });
                    })->whereIn('booking_containers.id', $agentAssignment);
                })
                    ->get()
            )

        ];
    }
}
