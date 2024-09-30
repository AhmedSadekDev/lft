<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class SimpleBookingContainer2Resource extends JsonResource
{

    public function toArray($request)
    {
        return [
            "id" => $this->id,
            'company_name' => $this->booking->company->name ?? "",
            // 'factory_name' => $this->booking?->thirdBookings?->factory?->name ?? "",
            'factory_name' => $this->branch->factory->name ?? "",
            'container_type'    => $this->container?->type ?? null,
            'branch'            => $this->branch?->name ?? null,
            'sail_of_number'    => $this->sail_of_number,
            'container_number'  => $this->container_no,
            'arrival_date'      => $this->arrival_date,
            "booking_number" => $this->booking?->booking_number ?? "",
            "container_size" => $this->container?->size ?? "",
            "shipping_agent" => $this->booking?->shipping_agent ?? "",
            "yard_title" => $this?->booking?->yard?->title ?? "",
            "yard_id" => $this?->booking?->yard?->id ?? "",
            "booking_id" => $this->booking_id ?? ""

        ];
    }
}
