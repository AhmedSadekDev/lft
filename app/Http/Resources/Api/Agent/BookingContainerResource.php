<?php

namespace App\Http\Resources\Api\Agent;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingContainerResource extends JsonResource
{

    public function toArray($request)
    {
        // تحديد المرحلة الحالية للحاوية
        $currentStage = 'specification';
        $isCompleted = false;
        $isApproved = false;

        if (!$this->superagent_specification_approved) {
            $currentStage = 'specification';
            $isCompleted = ($this->status >= 1 || !empty($this->specification_completed_at));
            $isApproved = false;
        } elseif (!$this->is_in_loading && !$this->superagent_loading_approved) {
            $currentStage = 'waiting';
            $isCompleted = true;
            $isApproved = true;
        } elseif (!$this->superagent_loading_approved) {
            $currentStage = 'loading';
            $isCompleted = ($this->status >= 2 || !empty($this->loading_completed_at));
            $isApproved = false;
        } elseif (!$this->superagent_unloading_approved) {
            $currentStage = 'unloading';
            $isCompleted = ($this->status >= 3 || !empty($this->unloading_completed_at));
            $isApproved = false;
        } else {
            $currentStage = 'finished';
            $isCompleted = true;
            $isApproved = true;
        }

        // في حال تم اعتماد المرحلة الحالية
        if ($currentStage === 'specification' && $this->superagent_specification_approved) {
            $isCompleted = true;
            $isApproved = true;
        } elseif ($currentStage === 'loading' && $this->superagent_loading_approved) {
            $isCompleted = true;
            $isApproved = true;
        } elseif ($currentStage === 'unloading' && $this->superagent_unloading_approved) {
            $isCompleted = true;
            $isApproved = true;
        }

        $stageStatus = 'pending';
        if ($isApproved) {
            $stageStatus = 'approved';
        } elseif ($isCompleted) {
            $stageStatus = 'completed';
        }

        // أحمر إذا كانت معلقة، أخضر إذا نفذ المندوب الإجراء أو اعتمدته الإدارة
        $statusColor = ($isCompleted || $isApproved) ? 'green' : 'red';

        return [
            "id" => $this->id,
            'company_name' => $this->booking->company->name ?? "",
            // 'factory_name' => $this->booking?->thirdBookings?->factory?->name ?? "",
            'factory_name' => $this->branch->factory->name ?? "",
            'container_type'    => $this->container?->type ?? null,
            'branch'            => $this->branch?->name ?? null,
            'sail_of_number'    => $this->sail_of_number,
            'container_number'  => $this->container_no,
            'arrival_date'      => $this->arrival_date,
            "booking_number" => $this->booking?->booking_number ?? "",
            "container_size" => $this->container?->size ?? "",
            "shipping_agent" => $this->booking?->shippingAgent->title ?? "",
            "yard_title" => $this?->booking?->yard?->title ?? "",
            "yard_id" => $this?->booking?->yard?->id ?? "",
            "booking_id" => $this->booking_id ?? "",
            "stage_type" => $currentStage,
            "stage_status" => $stageStatus,
            "is_completed" => $isCompleted,
            "is_approved" => $isApproved,
            "is_in_loading" => (int) ($this->is_in_loading ?? 0),
            "moved_to_loading_at" => $this->moved_to_loading_at,
            "status_color" => $statusColor,
            "card_header_color" => $statusColor,
            "can_upload_receipts" => true,
            "specification_approved_at" => $this->specification_approved_at,
            "loading_approved_at" => $this->loading_approved_at,
            "unloading_approved_at" => $this->unloading_approved_at,
            "notes" => NoteResource::collection($this->notes),
            'specification_latter' => optional($this->bookingPapers->where('type', 0)->last()?->image)->image ?? '',
            'container_image' => optional($this->bookingPapers->where('type', 1)->last()?->image)->image ?? '',
            'loading_answer' => optional($this->bookingPapers->where('type', 6)->last()?->image)->image ?? '',
            'container_with_sail_image' => optional($this->bookingPapers->where('type', 2)->last()?->image)->image ?? '',
            'unloading_image' => optional($this->bookingPapers->where('type', 4)->last()?->image)->image ?? '',
        ];
    }
}
