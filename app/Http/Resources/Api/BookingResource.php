<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    protected $containerId;

    public function __construct($resource, $containerId = null)
    {
        parent::__construct($resource);
        $this->containerId = $containerId;
    }

    public function toArray($request)
    {
        return [
            'container' => $this->bookingContainers?->first() 
    ? new ContainerResource($this->bookingContainers->first())
    : null,
            // باقي البيانات
            'status' => !is_null($this->last_movements) ? ($this->last_movements?->last()?->status ?? null) : null,
            'container_count' => $this->bookingContainers?->count() ?? 0,
            'employee' => $this->employee->name ?? null,
            'shipping_agency' => $this->shippingAgent->title ?? null,
            'Booking_number' => $this->booking_number ?? null,
            'custom_certificate_number' => $this->certificate_number ?? null,
            'type_of_action' => TypeOfAction($this->type_of_action) ?? null,
            'discharge_date' => $this->discharge_date ?? null,
            'permit_end_date' => $this->permit_end_date ?? null,
        ];
    }
}
