<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Booking;

class LoadingYardResource extends JsonResource
{

    public function toArray($request)
    {
        $agent = auth()->guard('agent')->user();
        
        $ids = app(\App\Services\ContainerStageService::class)->visibleContainers($agent->id, 1)->pluck('id');
        $bookingContainers = $this->bookingContainers()
            ->whereIn('booking_containers.id', $ids)
            ->with(['booking.company', 'booking.factory', 'booking.yard', 'branch.factory', 'container', 'stages'])
            ->get();

        return [
            "id" => $this->id,
            "title" => $this->title ?? "",
            "booking_containers" =>  $bookingContainers->map(fn ($container) => (new BookingContainerResource($container))->forStage(1))

        ];
    }
}

