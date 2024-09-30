<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FactoryResource extends JsonResource
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
            "name"          => $this->factory->name,
            "email"         => $this->email,
            "number"        => $this->number,
            "country"       => $this->country,
            "address"       => $this->address,
            "city"          => $this->city,
            "postal_code"   => 35511,
            "arrival_date"  => "22-05-2023"
        ];
    }
}
