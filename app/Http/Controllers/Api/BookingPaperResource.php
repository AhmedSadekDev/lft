<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingPaperResource extends JsonResource
{
    

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_text' => $this->type,
            'image' => $this->image,
        ];
    }
}
