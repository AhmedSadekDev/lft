<?php

namespace App\Http\Resources;

use App\Models\BookingPaper;
use App\Models\ContainerFollowUp;
use App\Services\ContainerFollowUpService;
use Illuminate\Http\Resources\Json\JsonResource;

class ContainerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $followUps = [];
        $papers = [];

        try {
            $followUps = app(ContainerFollowUpService::class)->timelineForContainer($this->resource);
            if ($followUps === [] && $this->relationLoaded('delivery_policies')) {
                $policy = $this->delivery_policies->first();
                if ($policy) {
                    $followUps = app(ContainerFollowUpService::class)->timelineForPolicy($policy);
                }
            }
        } catch (\Throwable $e) {
            $followUps = [];
        }

        try {
            $last = ContainerFollowUp::query()
                ->where('booking_container_id', $this->id)
                ->orderByDesc('occurred_at')
                ->first();
            $lastStatus = $last?->status_text;
        } catch (\Throwable $e) {
            $lastStatus = null;
        }

        try {
            if ($this->booking_id) {
                $papers = BookingPaper::with('image')
                    ->where('booking_id', $this->booking_id)
                    ->get()
                    ->map(fn ($paper) => [
                        'id' => $paper->id,
                        'type' => $paper->type,
                        'type_text' => $paper->type_text ?? null,
                        'image' => $paper->image?->image ?? null,
                    ])
                    ->values()
                    ->all();
            }
        } catch (\Throwable $e) {
            $papers = [];
        }

        return [
            'id'                => $this->id,
            'container_id'      => $this->container?->id ?? $this->container_id,
            'container_number'  => $this->container_no,
            'container_size'    => $this->container?->size,
            'container_type'    => $this->container?->type,
            'last_status'       => $lastStatus ?? ($this->status ?? 'Gate out empty'),
            'date'              => $this->arrival_date ?? now(),
            'follow_ups'        => $followUps,
            'documents'         => $papers,
        ];
    }
}
