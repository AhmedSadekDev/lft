<?php

namespace App\Http\Resources\Api\Superagent;

use Illuminate\Http\Resources\Json\JsonResource;

class SuperagentResource extends JsonResource
{
  
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "name" => $this->name ?? "",
            "phone" => $this->phone ?? "",
            "email" => $this->email ?? "",
            "token" => $this->session_id ?? "",
        ];
    }
}
