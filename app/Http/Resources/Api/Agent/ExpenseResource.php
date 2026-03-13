<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\DeliveryPolicy;

class ExpenseResource extends JsonResource
{
  
    public function toArray($request)
    {
		$image = '';
		if (!empty($this->image_agent_expenses)) {
			$image = asset('Admin/images/expenses/' . $this->image_agent_expenses);
		}
		
		return [
			"id" => $this->id,
			"title" => $this->title ?? "",
			"text" => $this->notes ?? "",
			"date" => $this->created_at ?? "",
			"value" => $this->value,
			"image" => $image,
			"booking_number" => $this->booking?->booking_number ?? $this->bookingContainer?->booking?->booking_number ?? null,
		];
    }
}
