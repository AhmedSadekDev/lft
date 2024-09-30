<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryPolicyResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "car" => $this->car ? new CarResource($this->car) : null,
            "driver" => $this->driver ? new DriverResource($this->driver) : null,
            "value" => $this?->money_transfer->value ?? "",
            "container_nos" => $this->booking_containers->pluck("container_no")->toArray(),
            "booking_number" => $this?->booking_containers?->first()?->booking?->booking_number ?? "",
            "date" => $this->created_at ?? "",
            "is_settled" => $this->is_settled

        ];
    }
}
