<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Agent\BookingResource;
use App\Http\Resources\Api\Agent\CarResource;
use App\Http\Resources\Api\Agent\LoadingYardResource;
use App\Http\Resources\Api\Agent\SimpleBookingContainer2Resource;
use App\Http\Resources\Api\Agent\SpecificationShippingAgentResource;
use App\Http\Resources\Api\Agent\UnloadingShippingAgentResource;
use App\Models\Booking;
use App\Models\Car;
use App\Models\shippingAgent;
use App\Models\Yard;
use Illuminate\Http\Request;

class BookingContainerAssignmentController extends Controller
{
    public function fetch_loading_assignments()
    {
        try {

            $agent = auth()->guard('agent')->user();
            $agent_booking_containers = $agent->agent_booking_containers()->wherePivot("created_at", ">=", now()->startOfDay())
                ->wherePivot("created_at", "<=", now()->endOfDay())->wherePivot("booking_container_status", 1)->orWherePivot('superagent_loading_approved', 0)->get();

            $yards = Yard::whereHas("bookingContainers", function ($qc) use ($agent_booking_containers) {
                $qc->where('superagent_specification_approved', 1)->where(function ($query) {
                    $query->where("status", 1)->orWhere('superagent_loading_approved', 0);
                })->whereIn("booking_containers.id", $agent_booking_containers->pluck("id")->toArray());
            })->orderBy("id", "desc")->get();
            // dd($yards);

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
            // get today assigments
            $agent_booking_containers = $agent->agent_booking_containers()->wherePivot("created_at", ">=", now()->startOfDay())
                ->wherePivot("created_at", "<=", now()->endOfDay())->wherePivot("booking_container_status", 0)->orWherePivot('superagent_specification_approved', 0)->get();


            // fetch shipping_agents that contain get today assigments
            $shipping_agent_ids = Booking::whereHas("bookingContainers", function ($qc) use ($agent_booking_containers) {
                $qc->where(function ($query) {
                    $query->where("status", 0)->orWhere('superagent_specification_approved', 0);
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
            // get today assigments
            $agent_booking_containers = $agent->agent_booking_containers()->wherePivot("created_at", ">=", now()->startOfDay())
                ->wherePivot("created_at", "<=", now()->endOfDay())->wherePivot("superagent_unloading_approved", 0)->wherePivot('superagent_specification_approved', 1)->wherePivot('superagent_loading_approved', 1)->get();

            // fetch shipping_agents that contain get today assigments
            $shipping_agent_ids = Booking::whereHas("bookingContainers", function ($qc) use ($agent_booking_containers) {
                $qc->where(function ($query) {
                    $query->where('superagent_unloading_approved', 0)->where('superagent_specification_approved', 1)->where('superagent_loading_approved', 1);
                })->whereIn("id", $agent_booking_containers->pluck("id")->toArray());
            })->orderBy("id", "desc")->get()->pluck("shipping_agent_id")->toArray();

            $shipping_agents = shippingAgent::whereIn("id", $shipping_agent_ids)->get();

            //return data
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
            // get bookings
            $booking_ids = $agent->agent_booking_containers()->wherePivot("created_at", ">=", now()->startOfDay())
                ->wherePivot("created_at", "<=", now()->endOfDay())->get()->pluck("booking_id")->toArray();

            // fetch bookings
            $bookings = Booking::whereIn("id", $booking_ids)->when($word != null, function ($q) use ($word) {
                $q->where("booking_number", "LIKE", "%$word%")->orWhereHas("bookingContainers", function ($q) use ($word) {
                    $q->where("container_no", "LIKE", "%$word%");
                });
            })->orderBy("id", "desc")->get();


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
            // get agent_booking_containers

            $agent_booking_containers = $agent->agent_booking_containers()
                ->whereDoesntHave('delivery_policies')
                ->where(function ($query) {
                    $query->where('booking_container_agents.created_at', '>=', now()->startOfDay())
                        ->where('booking_container_agents.created_at', '<=', now()->endOfDay());
                })
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
            $agent_booking_containers = $agent->booking_containers()->whereDate("created_at", now());
            $booking_containers = $agent->agent_booking_containers();

            // get agent_booking_containers specification
            $daily_specification_assignments = $agent_booking_containers->where("booking_container_status", 0); //->count();
            $finished_specification_assignments_count = $booking_containers->where("booking_containers.id", $daily_specification_assignments->get()->pluck("booking_container_id")->toArray())->where("status", "!=", 0)->count();

            $daily_specification_assignments_count = $daily_specification_assignments->count();

            $specification_assignments = [
                "daily_specification_assignments_count" => $daily_specification_assignments_count,
                "finished_specification_assignments_count" => $finished_specification_assignments_count,
            ];
            // get agent_booking_containers loading
            $daily_loading_assignments = $agent_booking_containers->where("booking_container_status", 1);
            $finished_loading_assignments_count = $booking_containers->where("booking_containers.id", $daily_loading_assignments->get()->pluck("booking_container_id")->toArray())->where("status", "!=", 1)->count();

            $daily_loading_assignments_count = $daily_loading_assignments->count();

            $loading_assignments = [
                "daily_loading_assignments_count" => $daily_loading_assignments_count,
                "finished_loading_assignments_count" => $finished_loading_assignments_count,
            ];

            // get agent_booking_containers unloading
            $daily_unloading_assignments = $agent_booking_containers->where("booking_container_status", 1);
            $finished_unloading_assignments_count = $booking_containers->where("booking_containers.id", $daily_unloading_assignments->get()->pluck("booking_container_id")->toArray())->where("status", "!=", 1)->count();

            $daily_unloading_assignments_count = $daily_unloading_assignments->count();

            $unloading_assignments = [
                "daily_unloading_assignments_count" => $daily_unloading_assignments_count,
                "finished_unloading_assignments_count" => $finished_unloading_assignments_count,
            ];

            $data = [
                "specification_assignments" => (object)$specification_assignments,
                "loading_assignments" => (object)$loading_assignments,
                "unloading_assignments" => (object)$unloading_assignments,
            ];

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(500, $Exception->getMessage());
        }
    }
}
