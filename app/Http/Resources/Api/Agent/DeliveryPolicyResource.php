<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryPolicyResource extends JsonResource
{

    public function toArray($request)
    {
        $firstContainer = $this->booking_containers->first();
        $value = $this->money_transfer?->value ?? 0;
        $officeCommission = $this->office_commission ?? 0;
        $actualDeduction = $value - $officeCommission;
        
        return [
            "id" => $this->id,
            "car" => $this->car ? new CarResource($this->car) : null,
            "driver" => $this->driver ? new DriverResource($this->driver) : null,
            "value" => $value,
            "office_commission" => $officeCommission,
            "actual_deduction" => $actualDeduction,
            "container_nos" => $this->booking_containers->pluck("container_no")->filter()->values()->toArray(),
            "booking_number" => $firstContainer?->booking?->booking_number ?? "",
            "branch_address" => $firstContainer?->branch?->address ?? "",
            "branch_name" => $firstContainer?->branch?->name ?? "",
            "date" => $this->date ?? "",
            "address" => $this->address ?? "",
            "is_settled" => $this->is_settled,
            "image" => $this->image?->image ?? "",
        ];
    }
}
