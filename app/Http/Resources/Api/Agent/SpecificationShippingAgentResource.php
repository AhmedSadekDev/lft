<?php

namespace App\Http\Resources\Api\Agent;

use App\Http\Resources\Api\Superagent\BookingResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SpecificationShippingAgentResource extends JsonResource
{

    public function toArray($request)
    {

        $agentAssignment = $request->user()->agent_booking_containers->pluck('id')->toArray();



        return [
            "id" => $this->id,
            "title" => $this->title ?? "",
            "bookings" =>  SpecificationBookingResource::collection(
                $this->bookings()->whereHas("bookingContainers", function ($q) use($agentAssignment) {
                    $q->where(function($query) {
                        $query->where("booking_containers.status", 0)->orWhere("booking_containers.superagent_specification_approved", 0);
                    })->whereIn('id', $agentAssignment);
                })
                    ->get()
            )

        ];
    }
}
