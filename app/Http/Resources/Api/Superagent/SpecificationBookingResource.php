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

        // تحديد المرحلة من الـ request
        $stage = $request->get('stage');

        $allContainers = $this->bookingContainers ?? collect();
        $hasBookingContainers = $allContainers->isNotEmpty();

        // حالة تنفيذ المندوب لكل مراحل الطلب
        $isSpecificationDone = $hasBookingContainers
            && $allContainers->every(fn ($container) => (int) $container->status >= 1);
        $isLoadingDone = $hasBookingContainers
            && $allContainers->every(fn ($container) => (int) $container->status >= 2);
        $isUnloadingDone = $hasBookingContainers
            && $allContainers->every(fn ($container) => (int) $container->status >= 3);

        // 1. Specification (التخصيص)
        $specificationContainers = $allContainers->filter(function ($container) {
            return (int) $container->status === 0 || ((int) $container->status === 1 && (int) $container->superagent_specification_approved === 0);
        })->values();

        // 2. Loading (التحميل)
        $loadingContainers = $allContainers->filter(function ($container) {
            return in_array((int) $container->status, [0, 1, 2])
                && (int) $container->superagent_specification_approved === 1
                && (int) $container->superagent_loading_approved === 0
                && (int) $container->superagent_unloading_approved === 0;
        })->values();

        // 3. Unloading (التعتيق)
        $unloadingContainers = $allContainers->filter(function ($container) {
            return in_array((int) $container->status, [0, 1, 2, 3])
                && (int) $container->superagent_specification_approved === 1
                && (int) $container->superagent_loading_approved === 1
                && (int) $container->superagent_unloading_approved === 0;
        })->values();

        // تحديد booking_containers بناءً على المرحلة المطلوبة
        if ($stage === 'loading') {
            $bookingContainers = $loadingContainers;
        } elseif ($stage === 'unloading') {
            $bookingContainers = $unloadingContainers;
        } elseif ($stage === 'specification') {
            $bookingContainers = $specificationContainers;
        } else {
            $bookingContainers = $allContainers;
        }

        return [
            "id" => $this->id,
            "booking_number" => $this->booking_number ?? "",
            "is_today" => $superagent_booking_containers->count() ? 1 : 0,
            "is_specification_done" => $isSpecificationDone ? 1 : 0,
            "is_loading_done" => $isLoadingDone ? 1 : 0,
            "is_unloading_done" => $isUnloadingDone ? 1 : 0,
            "booking_containers" => BookingContainerResource::collection($bookingContainers),
            "specification_containers" => BookingContainerResource::collection($specificationContainers),
            "loading_containers" => BookingContainerResource::collection($loadingContainers),
            "unloading_containers" => BookingContainerResource::collection($unloadingContainers),
        ];
    }
}
