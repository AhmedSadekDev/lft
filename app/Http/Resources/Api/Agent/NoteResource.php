<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
  
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "notes" => $this->notes ?? "",
            "attacher_name" => $this->attacher->name ?? "",
            "date" => $this->date ?? "",
            "time" => $this->time ?? "",
            "images" => $this->images->pluck("image")->toArray()

        ];
    }
}
