<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class SpecificationBookingResource extends JsonResource
{

    public function toArray($request)
    {
        $agentAssignment = $request->user()->agent_booking_containers->pluck('id')->toArray();


        return [
            "id" => $this->id,
            "booking_number" => $this->booking_number ?? "",
            "booking_containers" => BookingContainerResource::collection($this->bookingContainers->whereIn('id', $agentAssignment))
        ];
    }
}
