<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContainerResource extends JsonResource
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
            'id'                => $this->id,
            'container_id'      => $this->container->id,
            'container_number'  => $this->container_no,
            'container_size'    => $this->container->size,
            'container_type'    => $this->container->type,
            'last_status'       => $this->status ?? 'Gate out empty',
            'date'              => $this->created_at ?? now(),
        ];
    }
}
