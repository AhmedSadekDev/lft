<?php

namespace App\Http\Resources\Api\Desktop;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'id' => $this->id,
            'name' => $this->name,
            'ETA_CLIENT_ID' => $this->ETA_CLIENT_ID,
            'ETA_CLIENT_SECRET' => $this->ETA_CLIENT_SECRET,
            'pn' => $this->pn,
            'sn' => $this->sn,
        ];
    }
}
