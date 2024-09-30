<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "name" => $this->name ?? "",
            "phone" => $this->phone ?? "",
            "email" => $this->email ?? "",
            "token" => $this->session_id ?? "",
            "image" => $this->image ?? "",
        ];
    }
}
