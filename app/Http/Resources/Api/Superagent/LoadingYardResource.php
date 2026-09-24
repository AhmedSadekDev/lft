<?php

namespace App\Http\Resources\Api\Superagent;

use Illuminate\Http\Resources\Json\JsonResource;

class LoadingYardResource extends JsonResource
{

    public function toArray($request)
    {
        $superagent = auth()->guard('superagent')->user();
        $superagent_booking_containers = $superagent->superagent_booking_containers()->wherePivot("created_at", ">=", now()->startOfDay())
            ->wherePivot("created_at", "<=", now()->endOfDay())
            ->get();
            
        $bookingContainers = $this->bookingContainers()
            ->withoutInvoicedBooking()
            ->with(['booking.company', 'booking.factory', 'booking.yard', 'branch.factory', 'container', 'notes', 'agents'])
            ->where('superagent_loading_approved', 0)
            ->where('superagent_specification_approved', 1)
            ->where('is_in_loading', 1)
            ->get();


        return [
            "id" => $this->id,
            "title" => $this->title ?? "",
            "booking_containers" =>  BookingContainerResource::collection($bookingContainers)

        ];
    }
}

