<?php

namespace App\Http\Controllers\Api\Superagent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Agent\YardResource;
use App\Http\Resources\Api\Superagent\ShippingAgentResource;
use App\Models\Booking;
use App\Models\BookingContainerAgent;
use App\Models\DailyBookingContainer;
use App\Models\shippingAgent;
use App\Models\Yard;

class HomeController extends Controller
{
    /**
     * Super home dashboard: daily stage cards (تخصيص / تحميل / تعتيق) + active yards.
     *
     * GET /api/superagent/fetch_home
     */
    public function fetch_home()
    {
        try {
            $superagentId = auth()->guard('superagent')->id();

            $data = [
                'specification' => $this->stageStats($superagentId, 0),
                'loading' => $this->stageStats($superagentId, 1),
                'unloading' => $this->stageStats($superagentId, 2),
            ];

            $activeYards = Yard::query()
                ->withActiveYardContainersCount()
                ->havingRaw('active_containers_count > 0')
                ->orderBy('title')
                ->get();

            $data['active_yards_count'] = $activeYards->count();
            $data['active_yards'] = YardResource::collection($activeYards);

            $shippingAgentIds = Booking::withoutInvoice()
                ->whereHas('shippingAgent', function ($q) {
                    $q->whereHas('bookingContainers', function ($qc) {
                        $qc->whereIn('status', [0, 1]);
                    });
                })
                ->orderByDesc('id')
                ->pluck('shipping_agent_id')
                ->unique()
                ->filter()
                ->values()
                ->all();

            $data['shipping_agents'] = ShippingAgentResource::collection(
                shippingAgent::whereIn('id', $shippingAgentIds)->get()
            );

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $ex) {
            return $this->returnError(500, $ex->getMessage());
        }
    }

    /**
     * @return array{daily: int, assigned: int, finished: int, completed: int, percent: int}
     */
    private function stageStats(int $superagentId, int $status): array
    {
        $rows = DailyBookingContainer::query()
            ->where('superagent_id', $superagentId)
            ->where('booking_container_status', $status)
            ->whereDate('created_at', now())
            ->with('booking_container')
            ->get();

        $containerIds = $rows->pluck('booking_container_id')->filter()->unique()->values();
        $assignedIds = $containerIds->isEmpty()
            ? collect()
            : BookingContainerAgent::query()
                ->whereIn('booking_container_id', $containerIds)
                ->pluck('booking_container_id')
                ->unique();

        $assigned = 0;
        $finished = 0;

        foreach ($rows as $daily) {
            if ($assignedIds->contains($daily->booking_container_id)) {
                $assigned++;
            }
            // Completed when the live container status has moved past this daily stage.
            if ($daily->booking_container
                && (int) $daily->booking_container_status !== (int) $daily->booking_container->status
            ) {
                $finished++;
            }
        }

        $daily = $rows->count();

        return [
            'daily' => $daily,
            'assigned' => $assigned,
            'finished' => $finished,
            'completed' => $finished, // alias for UI: المكتمل
            'percent' => $daily > 0 ? (int) round(($finished / $daily) * 100) : 0,
        ];
    }
}
