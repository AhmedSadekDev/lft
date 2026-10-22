<?php

namespace App\Http\Resources\Api\Superagent;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Chip group for superagent unloading filters: one aging port (ميناء التعتيق).
 * Same shape as UnloadingShippingAgentResource so the mobile app can reuse chip UI.
 */
class UnloadingAgingPortResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => (int) ($this->resource['id'] ?? 0),
            'title' => (string) ($this->resource['title'] ?? ''),
            'entity' => 'aging_port',
            'booking_containers' => BookingContainerResource::collection(
                $this->resource['booking_containers'] ?? []
            ),
        ];
    }
}
