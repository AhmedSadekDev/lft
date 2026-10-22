<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class CarExpenseResource extends JsonResource
{
  
    public function toArray($request)
    {
        $registeredAt = $this->resource->getRawOriginal('created_at');
        $registeredTs = $registeredAt ? strtotime((string) $registeredAt) : false;

        return [
            'id' => $this->id,
            'title' => $this->title ?? '',
            'text' => $this->notes ?? '',
            'date' => $registeredTs ? date('Y-m-d', $registeredTs) : '',
            'time' => $registeredTs ? date('H:i', $registeredTs) : '',
            'registered_at' => $registeredTs ? date('Y-m-d H:i', $registeredTs) : '',
            'value' => $this->value,
        ];
    }
}
