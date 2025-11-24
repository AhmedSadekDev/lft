<?php

namespace App\Http\Resources\Api\Superagent;

use App\Models\DailyBookingContainer;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingContainerResource extends JsonResource
{

    public function toArray($request)
    {
        // dd($this);
        $superagent_id = auth()->guard("superagent")->id();
//["superagent_id","=",$superagent_id]
        $is_today = DailyBookingContainer::where([["booking_container_status","=",$this->status],["booking_container_id","=",$this->id]])->whereDate("created_at",now())->first();
        return [
            'id'                => $this->id,
            'company_name' => $this->booking->company->name ?? "",
            'factory_name' => $this->branch->factory->name ?? "",
            'container_type'    => $this->container?->type ?? null,
            'branch'            => $this->branch?->name ?? null,
            'sail_of_number'    => $this->sail_of_number,
            'container_number'  => $this->container_no,
            'arrival_date'      => $this->arrival_date,
            "booking_number" => $this->booking?->booking_number ?? "",
            "certificate_number" => $this->booking?->certificate_number ?? "",
            "container_size" => $this->container?->size ?? "",
            "shipping_agent" => $this->booking?->shipping_agent ?? "",
            'date'              => $this->created_at ?? "",
            // إضافات مطلوبة: بوليصة المكتب وتاريخ الخروج
            'office_policy'     => optional($this->delivery_policies->first()?->money_transfer)->value ?? null,
            'exit_date'         => $this->delivery_policies->first()->date ?? null,
            "yard_title" => $this?->booking?->yard?->title ?? "",
            "yard_id" => $this?->booking?->yard?->id ?? "",
            "notes" => NoteResource::collection($this->notes),
            "is_today" => $is_today ? 1 : 0,
            "booking_id" => $this->booking_id ?? "",
            'responsible_agents' => AgentResource::collection($this->agents()->wherePivot('booking_container_status', $this->status)->get()),
            'specification_latter' => optional($this->bookingPapers->where('type', 0)->last())->image->image ?? '',
            'container_image' => optional($this->bookingPapers->where('type', 1)->last())->image->image ?? '',
            'loading_answer' => optional($this->bookingPapers->where('type', 6)->last())->image->image ?? '',
            'container_with_sail_image' => optional($this->bookingPapers->where('type', 2)->last())->image->image ?? '',
            'unloading_image' => optional($this->bookingPapers->where('type', 3)->last())->image->image ?? '',

        ];
    }
}
