<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class SettingsResource extends JsonResource
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
            'phone'     => $this->phone ?? null,
            'email'     => $this->email ?? null,
            'whatsapp'  => $this->whatsapp ?? null,
            'facebook'  => $this->facebook ?? null,
            'twitter'   => $this->twitter ?? null,
            'linkedin'  => $this->linkedin ?? null,
            'instagram' => $this->instagram ?? null,
        ];
    }
}
