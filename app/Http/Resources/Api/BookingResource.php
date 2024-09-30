<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'containers' => !is_null($this->bookingContainers) ? ContainerResource::collection($this->bookingContainers) : null,

            'status' => !is_null($this->last_movements) ? ($this->last_movements?->last()?->status ?? null) : null,
            'container_count' => !is_null($this->bookingContainers) ? $this->bookingContainers->count() : 0,
            'employee' => !is_null($this->employee) ? $this->employee->name : null,
            'shipping_agency' => $this->shippingAgent->title ?? null,
            'Booking_number' => $this->booking_number ?? null,
            'custom_certificate_number' => $this->certificate_number ?? null,
            'type_of_action' => TypeOfAction($this->type_of_action) ?? null,
            'discharge_date' => $this->discharge_date ?? null,
            'permit_end_date' => $this->permit_end_date ?? null,
        ];

    }
}
