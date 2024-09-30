<?php

namespace App\Http\Resources\Api\Superagent;

use Illuminate\Http\Resources\Json\JsonResource;

class SpecificationShippingAgentResource extends JsonResource
{

    public function toArray($request)
    {
        $superagent = auth()->guard('superagent')->user();
        $superagent_booking_containers = $superagent->superagent_booking_containers()->wherePivot("created_at", ">=", now()->startOfDay())
            ->wherePivot("created_at", "<=", now()->endOfDay())
            //->wherePivot("booking_container_status", 0)
            ->get();
            
           

        return [
            "id" => $this->id,
            "title" => $this->title ?? "",
            "bookings" =>  BookingResource::collection(
                $this->bookings()->
                    whereHas("bookingContainers",function($q) use($superagent_booking_containers){
                        $q->where(function($query) {
                            $query->where("booking_containers.status",0)->orWhere("booking_containers.superagent_specification_approved",0);
                        })->whereIn("booking_containers.id",$superagent_booking_containers->pluck("id")->toArray());
                })
                    ->get()
            )
        ];
    }
}
