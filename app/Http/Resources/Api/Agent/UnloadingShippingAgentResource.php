<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class UnloadingShippingAgentResource extends JsonResource
{

    public function toArray($request)
    {
        $bookingContainers = collect();

        $agent = auth()->guard('agent')->user();

        $ids = app(\App\Services\ContainerStageService::class)->visibleContainers($agent->id, 2)->pluck('id');
        $bookingContainers = $this->bookingContainers()->whereIn('booking_containers.id', $ids)->with('stages')->get();

        return [
            "id" => $this->id,
            "title" => $this->title ?? "",
            "booking_containers" => $bookingContainers->map(fn ($container) => (new BookingContainerResource($container))->forStage(2))

        ];
    }
}
