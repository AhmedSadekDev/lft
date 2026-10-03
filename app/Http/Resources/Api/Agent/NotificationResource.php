<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
  
    public function toArray($request)
    {
        // `??` on a model property evaluates the accessor twice (__isset + __get); read each value once
        $notification = $this->resource;
        $title = $notification->getAttribute('title');
        $text = $notification->getAttribute('text');
        $date = $notification->getAttribute('date');
        $time = $notification->getAttribute('time');
        $typeId = $notification->getAttribute('type_id');

        return [
            "id" => $notification->getAttribute('id'),
            "title" => $title ?? "",
            "text" => $text ?? "",
            "type" => $notification->getAttribute('type'),
            "type_id" => $typeId,
            "booking_id" => $notification->bookingContainer?->booking_id,
            "booking_container_id" => $notification->getAttribute('booking_container_id'),
            "is_read" => $notification->getAttribute('is_read'),
            "date" => $date ?? "",
            "time" => $time ?? "",
            'action_type' => match ($typeId) {
                0 => 'specification',
                1 => 'loading',
                2 => 'unloading',
                default => null,
            },  
        ];
    }
}
