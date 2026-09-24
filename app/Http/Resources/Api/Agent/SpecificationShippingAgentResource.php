<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class SpecificationShippingAgentResource extends JsonResource
{

    public function toArray($request)
    {
        $agent = $request->user();
        $agentAssignment = $agent->agent_booking_containers()
            ->whereDoesntHave('booking.invoice')
            ->where(function ($q) {
                $q->where('booking_container_agents.stage_type', 0)
                    ->orWhereNull('booking_container_agents.stage_type');
            })
            ->where('booking_containers.superagent_specification_approved', 0)
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
                    ->with(['bookingContainers' => function ($q) use ($agentAssignment) {
                        $q->where('superagent_specification_approved', 0)
                            ->whereIn('id', $agentAssignment)
                            ->with(['booking.company', 'booking.factory', 'booking.yard', 'branch.factory', 'container', 'stages']);
                    }])
                    ->get()
            )
        ];
    }
}
