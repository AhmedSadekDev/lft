<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryPolicyDetailsResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "car" => $this->car ? new CarResource($this->car) : null,
            "driver" => $this->driver ? new DriverResource($this->driver) : null,
            "value" => $this?->money_transfer->value ?? "",
            "booking_containers" => SimpleBookingContainerResource::collection($this->booking_containers),
            "booking_number" => $this?->booking_containers?->first()?->booking?->booking_number ?? "",
            "date" => $this->created_at ?? "",
            "is_settled" => $this->is_settled


        ];
    }
}
class SimpleBookingContainerResource extends JsonResource
{
  
    public function toArray($request)
    {
    
       
        return [
            'id'                => $this->id,
            'container_type'    => $this->container?->type ?? null,
            'container_number'  => $this->container_no,
            "booking_number" => $this->booking?->booking_number ?? "",
            "container_size" => $this->container?->size ?? "",

            // 'factory_name' => $this->booking?->thirdBookings?->factory?->name ?? "",
            // 'factory_email' => $this->booking?->thirdBookings?->factory?->email ?? "",
            "booking_number" => $this->booking->booking_number ?? "",
            "container_size" => $this->container?->size ?? "",
            // 'factory_name' => $this->factory->name ?? "",
            'factory_name' => $this->branch->factory->name ?? "",
            'factory_email' => $this->branch->factory->email ?? "",

            // 'factory_email' => $this->factory->email ?? "",
            'arrival_date'      => $this->arrival_date,
            "yard_title" => $this->yard?->title ?? "",

        ];
    }
}
