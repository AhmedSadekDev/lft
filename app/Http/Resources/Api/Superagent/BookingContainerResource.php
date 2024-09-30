<?php

namespace App\Http\Resources\Api\Superagent;

use App\Models\DailyBookingContainer;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingContainerResource extends JsonResource
{

    public function toArray($request)
    {

        $superagent_id = auth()->guard("superagent")->id();

        $is_today = DailyBookingContainer::where([["superagent_id","=",$superagent_id],["booking_container_status","=",$this->status],["booking_container_id","=",$this->id]])->whereDate("created_at",now())->first();
        return [
            'id'                => $this->id,
            'company_name' => $this->booking->company->name ?? "",
            'factory_name' => $this->branch->factory->name ?? "",
            'container_type'    => $this->container?->type ?? null,
            'branch'            => $this->branch?->name ?? null,
            'sail_of_number'    => $this->sail_of_number,
            'container_number'  => $this->container_no,
            'arrival_date'      => $this->arrival_date,
            "booking_number" => $this->booking?->booking_number ?? "",
            "certificate_number" => $this->booking?->certificate_number ?? "",
            "container_size" => $this->container?->size ?? "",
            "shipping_agent" => $this->booking?->shipping_agent ?? "",
            'date'              => $this->created_at ?? "",
            "yard_title" => $this?->booking?->yard?->title ?? "",
            "yard_id" => $this?->booking?->yard?->id ?? "",
            "notes" => NoteResource::collection($this->notes),
            "is_today" => $is_today ? 1 : 0,
            "booking_id" => $this->booking_id ?? "",
            'responsible_agents' => AgentResource::collection($this->agents()->wherePivot('booking_container_status', $this->status)->get())

        ];
    }
}
