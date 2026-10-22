<?php

namespace App\Http\Resources\Api\Superagent;

use App\Http\Resources\Api\Agent\CarResource;
use App\Http\Resources\Api\Agent\DriverResource;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryPolicyResource extends JsonResource
{
    public function toArray($request)
    {
        $firstContainer = $this->booking_containers->first();
        $value = $this->money_transfer?->value ?? 0;
        $officeCommission = $this->office_commission ?? 0;
        $agent = $this->money_transfer?->transferer;

        return [
            'id' => $this->id,
            'car' => $this->car ? new CarResource($this->car) : null,
            'driver' => $this->driver ? new DriverResource($this->driver) : null,
            'agent_id' => $this->money_transfer?->transferer_id,
            'agent_name' => is_object($agent) ? ($agent->name ?? '') : '',
            'value' => $value,
            'office_commission' => $officeCommission,
            'actual_deduction' => $value - $officeCommission,
            'custody_usd' => $this->custody_usd !== null ? (float) $this->custody_usd : null,
            'custody_usd_is_record_only' => true,
            'container_nos' => $this->booking_containers->pluck('container_no')->filter()->values()->toArray(),
            'booking_number' => $firstContainer?->booking?->booking_number ?? '',
            'booking_id' => $firstContainer?->booking_id,
            'departure' => $firstContainer?->departure?->title ?? '',
            'loading' => $firstContainer?->loading?->title ?? '',
            'aging_port' => $firstContainer?->aging?->title ?? '',
            'aging_id' => $firstContainer?->aging_id,
            'date' => $this->date ?? '',
            'address' => $this->address ?? '',
            'is_settled' => $this->is_settled,
            'image' => $this->image?->image ?? '',
        ];
    }
}
