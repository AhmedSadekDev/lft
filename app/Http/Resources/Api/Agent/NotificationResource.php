<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
  
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "title" => $this->title ?? "",
            "text" => $this->text ?? "",
            "date" => $this->date ?? "",
            "time" => $this->time ?? ""
        ];
    }
}
