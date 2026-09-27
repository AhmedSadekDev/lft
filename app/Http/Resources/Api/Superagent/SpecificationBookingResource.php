<?php

namespace App\Http\Resources\Api\Superagent;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\BookingContainer;

class SpecificationBookingResource extends JsonResource
{

    public function toArray($request)
    {
        $superagent_booking_containers = BookingContainer::where('created_at', '>=', now()->startOfDay())
            ->where('created_at', '<=', now()->endOfDay())
            ->where(function ($query) {
                $query->whereIn('status', [0, 1, 2, 3]);
            })->get();

        $stage = $request->get('stage');
        $stageContainers = ($this->bookingContainers ?? collect())->values();
        $empty = collect();

        if ($stage === 'specification') {
            $specificationContainers = $stageContainers;
            $waitingContainers = $empty;
            $loadingContainers = $empty;
            $unloadingContainers = $empty;
            $bookingContainers = $specificationContainers;
        } elseif ($stage === 'waiting') {
            $specificationContainers = $empty;
            $waitingContainers = $stageContainers;
            $loadingContainers = $empty;
            $unloadingContainers = $empty;
            $bookingContainers = $waitingContainers;
        } elseif ($stage === 'loading') {
            $specificationContainers = $empty;
            $waitingContainers = $empty;
            $loadingContainers = $stageContainers;
            $unloadingContainers = $empty;
            $bookingContainers = $loadingContainers;
        } elseif ($stage === 'unloading') {
            $specificationContainers = $empty;
            $waitingContainers = $empty;
            $loadingContainers = $empty;
            $unloadingContainers = $stageContainers;
            $bookingContainers = $unloadingContainers;
        } else {
            $specificationContainers = $stageContainers->filter(function ($container) {
                return (int) $container->status === 0
                    || ((int) $container->status === 1 && (int) $container->superagent_specification_approved === 0);
            })->values();
            $waitingContainers = $stageContainers->filter(function ($container) {
                return (int) $container->superagent_specification_approved === 1
                    && (int) $container->is_in_loading === 0
                    && (int) $container->superagent_loading_approved === 0
                    && (int) $container->superagent_unloading_approved === 0;
            })->values();
            $loadingContainers = $stageContainers->filter(function ($container) {
                return (int) $container->superagent_specification_approved === 1
                    && (int) $container->is_in_loading === 1
                    && (int) $container->superagent_loading_approved === 0
                    && (int) $container->superagent_unloading_approved === 0;
            })->values();
            $unloadingContainers = $stageContainers->filter(function ($container) {
                return (int) $container->superagent_specification_approved === 1
                    && (int) $container->superagent_loading_approved === 1
                    && (int) $container->superagent_unloading_approved === 0;
            })->values();
            $bookingContainers = $stageContainers;
        }

        $allForFlags = $stage ? $bookingContainers : $stageContainers;
        $completionFlags = [];
        foreach (\App\Services\ContainerStageService::NAMES as $type => $name) {
            // Operational completion precedes superagent approval. Status supports legacy rows.
            $completionFlags["is_{$name}_done"] = (int) ($allForFlags->isNotEmpty()
                && $allForFlags->every(fn ($container) => $container->isStageCompleted($type)));
        }
        // Keep waiting done when containers advance to loading or unloading.
        $completionFlags['is_waiting_done'] = $stage ? (int) in_array($stage, ['waiting', 'loading', 'unloading'], true) : (int) ($allForFlags->isNotEmpty()
            && $allForFlags->every(fn ($container) =>
                (int) $container->superagent_specification_approved === 1
                || $container->isStageCompleted(1)
                || $container->isStageCompleted(2)));

        return [
            "id" => $this->id,
            "booking_container_id" => $this->when($bookingContainers->count() === 1, fn () => $bookingContainers->first()->id),
            "booking_number" => $this->booking_number ?? "",
            "is_today" => $superagent_booking_containers->count() ? 1 : 0,
            "is_specification_done" => $completionFlags['is_specification_done'],
            "is_waiting_done" => $completionFlags['is_waiting_done'],
            "is_loading_done" => $completionFlags['is_loading_done'],
            "is_unloading_done" => $completionFlags['is_unloading_done'],
            "booking_containers" => BookingContainerResource::collection($bookingContainers),
            "specification_containers" => BookingContainerResource::collection($specificationContainers),
            "waiting_containers" => BookingContainerResource::collection($waitingContainers),
            "loading_containers" => BookingContainerResource::collection($loadingContainers),
            "unloading_containers" => BookingContainerResource::collection($unloadingContainers),
        ];
    }
}
