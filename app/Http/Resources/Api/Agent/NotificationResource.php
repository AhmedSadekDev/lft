<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
  
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "title" => $this->title ?? "",
            "text" => $this->text ?? "",
            "type" => $this->type,
            "type_id" => $this->type_id,
            "booking_id" => $this->bookingContainer?->booking_id,
            "booking_container_id" => $this->booking_container_id,
            "date" => $this->date ?? "",
            "time" => $this->time ?? ""
        ];
    }
}
