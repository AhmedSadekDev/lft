<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class SpecificationShippingAgentResource extends JsonResource
{

    public function toArray($request)
    {
        $agentAssignment = $request->user()->agent_booking_containers()->wherePivot('stage_type', 0)
            ->whereDoesntHave('booking.invoice')
            ->where('booking_container_agents.superagent_specification_approved', 0)
            ->pluck('booking_containers.id')
            ->toArray();

        return [
            "id" => $this->id,
            "title" => $this->title ?? "",
            "bookings" => SpecificationBookingResource::collection(
                $this->bookings()->whereHas("bookingContainers", function ($q) use ($agentAssignment) {
                    $q->where("booking_containers.superagent_specification_approved", 0)
                        ->whereIn('booking_containers.id', $agentAssignment);
                })
                    ->get()
            )

        ];
    }
}
