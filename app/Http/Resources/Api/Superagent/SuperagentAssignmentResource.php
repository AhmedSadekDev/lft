<?php

namespace App\Http\Resources\Api\Superagent;

use Illuminate\Http\Resources\Json\JsonResource;

class SuperagentAssignmentResource extends JsonResource
{
    public function toArray($request)
    {
        // الـ resource هنا بياخد Array جاهزة (type, entity, id, title, items_type, items)
        return [
            'type'       => $this['type'],          // specification | loading | unloading
            'entity'     => $this['entity'],        // shipping_agent | yard
            'id'         => $this['id'],
            'title'      => $this['title'] ?? '',
            'items_type' => $this['items_type'],    // bookings | booking_containers
            'items'      => $this['items'],         // Collection من BookingResource أو BookingContainerResource
        ];
    }
}
