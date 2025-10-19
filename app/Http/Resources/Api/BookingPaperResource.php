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
            'type_text' => $this->type_text(),
            'image' => $this->image?->image ?? null,
        ];
    }

    public function type_text()
    {
        switch ($this->type) {
            case 0:
                return 'جواب تخصيص';
            case 1:
                return 'صوره الحاويه';
            case 5:
                return 'صورة سيل ملاحي';
            case 4:
                return 'جواب التعتيق';
            default:
                return 'صوره اخري';
        }
    }
}
