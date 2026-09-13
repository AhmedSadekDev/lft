<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Agent\BookingResource;
use App\Http\Resources\Api\Agent\CarResource;
use App\Http\Resources\Api\Agent\LoadingYardResource;
use App\Http\Resources\Api\Agent\SimpleBookingContainer2Resource;
use App\Http\Resources\Api\Agent\SpecificationShippingAgentResource;
use App\Http\Resources\Api\Agent\UnloadingShippingAgentResource;
use App\Models\Agent;
use App\Models\Booking;
use App\Models\Car;
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
            $cutoff = now()->subHours(24);
            $agent_booking_containers = $agent->agent_booking_containers()
                ->wherePivot('stage_type', 0)
                ->where(function ($q) use ($cutoff) {
                    $q->where('booking_container_agents.superagent_specification_approved', 0)
                      ->orWhere(function ($q2) use ($cutoff) {
                          $q2->where('booking_container_agents.superagent_specification_approved', 1)
                             ->where(function ($q3) use ($cutoff) {
                                 $q3->where('booking_container_agents.specification_approved_at', '>=', $cutoff)
                                    ->orWhereNull('booking_container_agents.specification_approved_at')
                                    ->orWhere('booking_container_agents.updated_at', '>=', $cutoff);
                             });
                      });
                })
                ->get();

            // fetch shipping_agents that contain assignments
            $shipping_agent_ids = Booking::whereHas("bookingContainers", function ($qc) use ($agent_booking_containers, $cutoff) {
                $qc->where(function ($query) use ($cutoff) {
                    $query->where('booking_containers.superagent_specification_approved', 0)
                          ->orWhere(function ($q2) use ($cutoff) {
                              $q2->where('booking_containers.superagent_specification_approved', 1)
                                 ->where(function ($q3) use ($cutoff) {
                                     $q3->where('booking_containers.specification_approved_at', '>=', $cutoff)
                                        ->orWhereNull('booking_containers.specification_approved_at')
                                        ->orWhere('booking_containers.updated_at', '>=', $cutoff);
                                 });
                          });
                })->whereIn("id", $agent_booking_containers->pluck("id")->toArray());
            })
                ->orderBy("id", "desc")->get()->pluck("shipping_agent_id")->toArray();



            $shipping_agents = shippingAgent::whereIn("id", $shipping_agent_ids)->get();

            //return data
            $data = SpecificationShippingAgentResource::collection($shipping_agents);


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
                    $q->where("booking_number", "LIKE", "%$word%")->orWhereHas("bookingContainers", function ($q) use ($word) {
                        $q->where("container_no", "LIKE", "%$word%");
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
            // get agent_booking_containers

            $agent_booking_containers = \App\Models\BookingContainer::whereHas('agents', fn ($q) => $q->where('agents.id', $agent->id))
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
