<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Agent\BookingResource;
use App\Http\Resources\Api\Agent\LoadingYardResource;
use App\Http\Resources\Api\Agent\SimpleBookingContainer2Resource;
use App\Http\Resources\Api\Agent\UnloadingShippingAgentResource;
use App\Models\Agent;
use App\Models\Booking;
use App\Models\BookingContainer;
use App\Models\Invoice;
use App\Models\shippingAgent;
use App\Models\Yard;
use Illuminate\Http\Request;

class BookingContainerAssignmentController extends Controller
{
    public function fetch_loading_assignments()
    {
        try {

            $agent = auth()->guard('agent')->user();
            /** @var Agent $agent */
            $ids = app(\App\Services\ContainerStageService::class)->visibleContainers($agent->id, 1)->pluck('id');
            $yards = Yard::whereHas('bookingContainers', fn ($q) => $q->whereIn('booking_containers.id', $ids))->orderByDesc('id')->get();

            $data = LoadingYardResource::collection($yards);


            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(500, $Exception->getMessage());
        }
    }

    public function fetch_specification_assignments()
    {
        try {
            $agent = auth()->guard('agent')->user();
            /** @var Agent $agent */

            // نفس أسلوب التحميل: حاويات المندوب مباشرة بدون اعتماد على Resource يعيد الاستعلام
            $containers = BookingContainer::query()
                ->where(function ($q) {
                    $q->where('superagent_specification_approved', 0)
                        ->orWhereNull('superagent_specification_approved');
                })
                ->whereDoesntHave('booking.invoice')
                ->whereHas('agents', function ($q) use ($agent) {
                    $q->where('agents.id', $agent->id)
                        ->where(function ($stage) {
                            $stage->where('booking_container_agents.stage_type', 0)
                                ->orWhereNull('booking_container_agents.stage_type');
                        });
                })
                ->with([
                    'booking.company',
                    'booking.factory',
                    'booking.yard',
                    'booking.shippingAgent',
                    'branch.factory',
                    'container',
                    'stages',
                ])
                ->orderByDesc('id')
                ->get();

            if ($containers->isEmpty()) {
                return $this->returnAllData([], __('alerts.success'));
            }

            $data = $containers
                ->groupBy(function (BookingContainer $container) {
                    $shippingId = $container->booking?->shipping_agent_id;
                    if ($shippingId) {
                        return 'sa_'.$shippingId;
                    }

                    return 'yard_'.($container->booking?->yard_id ?: 0);
                })
                ->map(function ($group) {
                    /** @var BookingContainer $first */
                    $first = $group->first();
                    $shippingId = $first->booking?->shipping_agent_id;
                    $title = $shippingId
                        ? ($first->booking?->shippingAgent?->title ?? '')
                        : ($first->booking?->yard?->title ?? 'غير محدد');

                    return [
                        'id' => $shippingId ?: ($first->booking?->yard_id ?: 0),
                        'title' => $title,
                        'bookings' => $group->groupBy('booking_id')->map(function ($bookingContainers) {
                            $booking = $bookingContainers->first()->booking;

                            return [
                                'id' => $booking?->id,
                                'booking_number' => $booking?->booking_number ?? '',
                                'booking_containers' => $bookingContainers->map(
                                    fn (BookingContainer $container) => (new \App\Http\Resources\Api\Agent\BookingContainerResource($container))->forStage(0)
                                )->values(),
                            ];
                        })->values(),
                    ];
                })
                ->values()
                ->all();

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(500, $Exception->getMessage());
        }
    }

    public function fetch_unloading_assignments()
    {
        try {

            $agent = auth()->guard('agent')->user();
            /** @var Agent $agent */
            $ids = app(\App\Services\ContainerStageService::class)->visibleContainers($agent->id, 2)->pluck('id');
            $shipping_agents = shippingAgent::whereHas('bookingContainers', fn ($q) => $q->whereIn('booking_containers.id', $ids))->orderByDesc('id')->get();

            $data = UnloadingShippingAgentResource::collection($shipping_agents);


            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(500, $Exception->getMessage());
        }
    }
    public function fetch_bookings(Request $request)
    {
        try {
            $word = $request->word;

            $agent = auth()->guard('agent')->user();
            /** @var Agent $agent */
            // get bookings IDs for this agent (without date filter)
            $booking_ids = $agent->agent_booking_containers()->get()->pluck("booking_id")->unique()->toArray();

            // get booking IDs that have invoices
            $bookings_with_invoices = Invoice::whereIn('booking_id', $booking_ids)->pluck('booking_id')->toArray();

            // exclude bookings that have invoices
            $booking_ids_without_invoices = array_diff($booking_ids, $bookings_with_invoices);

            // fetch bookings that don't have an invoice
            $bookings = Booking::whereIn("id", $booking_ids_without_invoices)
                ->when($word != null, function ($q) use ($word) {
                    $q->where(function ($search) use ($word) {
                        $search->where("booking_number", "LIKE", "%$word%")
                            ->orWhereHas("bookingContainers", fn ($containers) => $containers->where("container_no", "LIKE", "%$word%"));
                    });
                })
                ->orderBy("id", "desc")
                ->get();


            //return data
            $data = BookingResource::collection($bookings);


            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(500, $Exception->getMessage());
        }
    }
    public function fetch_booking_containers()
    {
        try {

            $agent = auth()->guard('agent')->user();
            /** @var Agent $agent */
            $service = app(\App\Services\ContainerStageService::class);

            // Collect visible containers across all stages.
            // visibleContainers() already filters by:
            //   - agent assignment for that stage type
            //   - receipts_closed_at (container disappears once receipts are closed)
            //   - invoice existence (whereDoesntHave booking.invoice)
            $ids = $service->visibleContainers($agent->id, 0)
                ->pluck('id')
                ->merge($service->visibleContainers($agent->id, 1)->pluck('id'))
                ->merge($service->visibleContainers($agent->id, 2)->pluck('id'))
                ->unique();

            $agent_booking_containers = \App\Models\BookingContainer::whereIn('id', $ids)
                ->whereDoesntHave('delivery_policies')
                ->get();

            //return data
            $data = SimpleBookingContainer2Resource::collection($agent_booking_containers);


            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(500, $Exception->getMessage());
        }
    }

    public function fetch_home_statistics()
    {
        try {

            $agent = auth()->guard('agent')->user();
            /** @var Agent $agent */
            $data = [];
            foreach (\App\Services\ContainerStageService::NAMES as $type => $name) {
                $query = app(\App\Services\ContainerStageService::class)->visibleContainers($agent->id, $type);
                if ($type === 0) {
                    $query->where('superagent_specification_approved', 0);
                }
                $data[$name.'_assignments'] = [
                    'daily_'.$name.'_assignments_count' => (clone $query)->count(),
                    'finished_'.$name.'_assignments_count' => (clone $query)->where('status', '>=', $type + 1)->count(),
                ];
            }

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(500, $Exception->getMessage());
        }
    }
}
