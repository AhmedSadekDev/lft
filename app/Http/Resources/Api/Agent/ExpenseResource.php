<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray($request)
    {
        $image = '';
        if (! empty($this->image_agent_expenses)) {
            $image = asset('Admin/images/expenses/'.$this->image_agent_expenses);
        }

        // Models strip time via getCreatedAtAttribute (Y-m-d only); use raw for registration datetime.
        $registeredAt = $this->resource->getRawOriginal('created_at');
        $registeredTs = $registeredAt ? strtotime((string) $registeredAt) : false;

        return [
            'id' => $this->id,
            'version' => $this->version,
            'type_id' => $this->type_id,
            'booking_container_id' => $this->booking_container_id,
            'booking_id' => $this->booking_id,
            'request_key' => $this->request_key,
            'voided_at' => $this->voided_at,
            'title' => $this->title ?? '',
            'text' => $this->notes ?? '',
            'date' => $registeredTs ? date('Y-m-d', $registeredTs) : '',
            'time' => $registeredTs ? date('H:i', $registeredTs) : '',
            'registered_at' => $registeredTs ? date('Y-m-d H:i', $registeredTs) : '',
            'value' => $this->value,
            'image' => $image,
            'booking_number' => $this->booking?->booking_number ?? $this->bookingContainer?->booking?->booking_number ?? null,
        ];
    }
}
