<?php

namespace App\Http\Resources\Api\Superagent;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{

    public function toArray($request)
    {
        $superagent = auth()->guard('superagent')->user();
        $superagent_booking_containers = $superagent->superagent_booking_containers()
            ->wherePivot("created_at", ">=", now()->startOfDay())
            ->wherePivot("created_at", "<=", now()->endOfDay())
            ->where(function($q) {
                // الحاويات التي تحتاج موافقة على التخصيص:
                // 1. booking_container_status = 0 (لم يتم التخصيص بعد)
                // 2. booking_container_status = 1 و superagent_specification_approved = 0 (تم التخصيص لكن لم يتم الموافقة)
                $q->wherePivot("booking_container_status", 0)
                  ->orWhere(function($q2) {
                      $q2->wherePivot("booking_container_status", 1)
                         ->wherePivot('superagent_specification_approved', 0);
                  });
            })
            ->get();



        return [
            "id" => $this->id,
            "booking_number" => $this->booking_number ?? "",
            "is_today" => $superagent_booking_containers->count() ? 1 : 0,
            "booking_containers" => BookingContainerResource::collection($this->bookingContainers()->where(function($query) {
                // الحاويات التي تحتاج موافقة على التخصيص:
                // 1. status = 0 (لم يتم التخصيص بعد)
                // 2. status = 1 و superagent_specification_approved = 0 (تم التخصيص لكن لم يتم الموافقة)
                $query->where("booking_containers.status", 0)
                      ->orWhere(function($q2) {
                          $q2->where("booking_containers.status", 1)
                             ->where('booking_containers.superagent_specification_approved', 0);
                      });
            })->whereIn("booking_containers.id",$superagent_booking_containers->pluck("id")->toArray())->get())
        ];
    }
}
