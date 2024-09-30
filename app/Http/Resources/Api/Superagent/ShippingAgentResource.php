<?php

namespace App\Http\Resources\Api\Superagent;

use Illuminate\Http\Resources\Json\JsonResource;

class ShippingAgentResource extends JsonResource
{

    public function toArray($request)
    {

        return [
            "id" => $this->id,
            "title" => $this->title ?? ""
        ];
    }
}


