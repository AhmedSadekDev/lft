<?php

namespace App\Http\Resources\Api\Superagent;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\BookingContainer;

class SpecificationBookingResource extends JsonResource
{

    public function toArray($request)
    {
        $superagent = auth()->guard('superagent')->user();
        // $superagent_booking_containers = $superagent->superagent_booking_containers()->wherePivot("created_at", ">=", now()->startOfDay())
        //     ->wherePivot("created_at", "<=", now()->endOfDay())->wherePivot("booking_container_status", 0)->orWherePivot('superagent_specification_approved', 0)->get();
        $superagent_booking_containers = BookingContainer::where('created_at', '>=', now()->startOfDay())
            ->where('created_at', '<=', now()->endOfDay())
            ->where(function ($query) {
            $query->whereIn('status', [0, 1, 2, 3]);
        })->get();

        // تحديد المرحلة من الـ request أو من context
        $stage = $request->get('stage', 'specification');

        // حالة تنفيذ المندوب لكل مراحل الطلب.
        // تُعتبر المرحلة منتهية عندما تنتهي في جميع حاويات الطلب.
        $allBookingContainers = $this->bookingContainers()
            ->get(['booking_containers.id', 'booking_containers.status']);
        $hasBookingContainers = $allBookingContainers->isNotEmpty();

        $isSpecificationDone = $hasBookingContainers
            && $allBookingContainers->every(fn ($container) => (int) $container->status >= 1);
        $isLoadingDone = $hasBookingContainers
            && $allBookingContainers->every(fn ($container) => (int) $container->status >= 2);
        $isUnloadingDone = $hasBookingContainers
            && $allBookingContainers->every(fn ($container) => (int) $container->status >= 3);

        // فلترة الحاويات حسب المرحلة
        $bookingContainersQuery = $this->bookingContainers();

        if ($stage === 'loading') {
            // شروط التحميل: status في [0,1,2] و superagent_specification_approved = 1 و superagent_loading_approved = 0
            $bookingContainersQuery->whereIn('booking_containers.status', [0, 1, 2])
                ->where('booking_containers.superagent_specification_approved', 1)
                ->where('booking_containers.superagent_loading_approved', 0)
                ->where('booking_containers.superagent_unloading_approved', 0);
        } elseif ($stage === 'unloading') {
            // شروط التعتيق: superagent_specification_approved = 1 و superagent_loading_approved = 1 و superagent_unloading_approved = 0
            $bookingContainersQuery->whereIn('booking_containers.status', [0, 1, 2, 3])
                ->where('booking_containers.superagent_specification_approved', 1)
                ->where('booking_containers.superagent_loading_approved', 1)
                ->where('booking_containers.superagent_unloading_approved', 0);
        } else {
            // شروط التخصيص (افتراضي):
            // 1. status = 0 (لم يتم التخصيص بعد)
            // 2. status = 1 و superagent_specification_approved = 0 (تم التخصيص لكن لم يتم الموافقة)
            $bookingContainersQuery->where(function($q) {
                $q->where('booking_containers.status', 0)
                  ->orWhere(function($q2) {
                      $q2->where('booking_containers.status', 1)
                         ->where('booking_containers.superagent_specification_approved', 0);
                  });
            });
        }

        return [
            "id" => $this->id,
            "booking_number" => $this->booking_number ?? "",
            "is_today" => $superagent_booking_containers->count() ? 1 : 0,
            "is_specification_done" => $isSpecificationDone ? 1 : 0,
            "is_loading_done" => $isLoadingDone ? 1 : 0,
            "is_unloading_done" => $isUnloadingDone ? 1 : 0,
            "booking_containers" => BookingContainerResource::collection($bookingContainersQuery->get())
        ];
    }
}
