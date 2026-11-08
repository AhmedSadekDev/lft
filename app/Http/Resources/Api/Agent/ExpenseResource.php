<?php

namespace App\Http\Resources\Api\Agent;

use App\Models\MoneyTransfer;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray($request)
    {
        $image = '';
        if (! empty($this->image_agent_expenses)) {
            $image = asset('Admin/images/expenses/'.$this->image_agent_expenses);
        }

        // Models strip time via getCreatedAtAttribute (Y-m-d only); use raw for registration datetime.
        $registeredAt = $this->resource->getRawOriginal('created_at');
        $registeredTs = $registeredAt ? strtotime((string) $registeredAt) : false;

        $isTransfer = $this->resource instanceof MoneyTransfer;
        $transfererName = null;
        $title = $this->title ?? '';
        if ($isTransfer) {
            $this->resource->loadMissing('transferer');
            $transfererName = $this->transferer?->name ?: null;
            if ($transfererName) {
                $title = 'تحويل عهدة من ' . $transfererName;
            }
        }

        return [
            'id' => $this->id,
            'version' => $this->version,
            'type_id' => $this->type_id,
            'booking_container_id' => $this->booking_container_id,
            'booking_id' => $this->booking_id,
            'request_key' => $this->request_key,
            'voided_at' => $this->voided_at,
            'title' => $title,
            'text' => $this->notes ?? '',
            'date' => $registeredTs ? date('Y-m-d', $registeredTs) : '',
            'time' => $registeredTs ? date('H:i', $registeredTs) : '',
            'registered_at' => $registeredTs ? date('Y-m-d H:i', $registeredTs) : '',
            'value' => $this->value,
            'image' => $image,
            'booking_number' => $this->booking?->booking_number ?? $this->bookingContainer?->booking?->booking_number ?? null,
            'is_money_transfer' => $isTransfer,
            'transferer_name' => $transfererName,
            'under_review' => $isTransfer ? false : (
                ! $this->admin_approval
                && ! $this->booking_service_id
                && ! $this->voided_at
            ),
        ];
    }
}
