<?php

namespace App\Http\Resources\Api\Superagent;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\Superagent\AgentResource;

class SimpleBookingContainerResource extends JsonResource
{

    public function toArray($request)
    {

        return [
            'id'       => $this->id,
            'factory_name' => $this->factory_name,
            'container_number'  => $this->container_no,
            'arrival_date'      => $this->arrival_date,
            "yard_title" => $this?->booking?->yard?->title ?? "",
            "yard_id" => $this?->booking?->yard?->id ?? "",
            'responsible_agents' => AgentResource::collection($this->agents()->wherePivot('stage_type', $request->filled('type_id') ? (int) $request->type_id : app(\App\Services\ContainerStageService::class)->currentType($this->resource))->get())
        ];
    }
}
