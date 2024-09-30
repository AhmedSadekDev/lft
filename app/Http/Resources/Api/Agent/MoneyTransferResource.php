<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class MoneyTransferResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            "value" => $this->value ?? "",
            "date" => $this->created_at ?? "",

        ];
    }
}
