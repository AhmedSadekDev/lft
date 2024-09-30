<?php

namespace App\Http\Resources\Api\Superagent;

use Illuminate\Http\Resources\Json\JsonResource;

class SimpleBookingContainerResource extends JsonResource
{

    public function toArray($request)
    {

        return [
            'id'       => $this->id,
            'factory_name' => $this->branch->factory->name ?? "",
            'container_number'  => $this->container_no,
            'arrival_date'      => $this->arrival_date,
            "yard_title" => $this?->booking?->yard?->title ?? "",
            "yard_id" => $this?->booking?->yard?->id ?? "",
            'responsible_agents' => AgentResource::collection($this->agents()->wherePivot('booking_container_status', $this->status)->get())
        ];
    }
}
