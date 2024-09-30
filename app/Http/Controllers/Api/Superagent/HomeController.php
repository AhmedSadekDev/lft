<?php

namespace App\Http\Controllers\Api\Superagent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Superagent\ShippingAgentResource;
use App\Models\Booking;
use App\Models\BookingContainerAgent;
use App\Models\DailyBookingContainer;
use App\Models\shippingAgent;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function fetch_home()
    {
        try {
            //->where([["booking_container_id", "=", $specification_daily->booking_container_id], ["booking_container_status", "=", $specification_daily?->booking_container?->status]

            $specification_daily_booking_containers =  DailyBookingContainer::whereDate("created_at", now())->where("superagent_id", auth()->guard("superagent")->id())->where("booking_container_status", 0)->get();

            $specification_assigned_booking_containers = 0;
            $specification_finished_booking_containers = 0;

            $loading_daily_booking_containers =  DailyBookingContainer::whereDate("created_at", now())->where("superagent_id", auth()->guard("superagent")->id())->where("booking_container_status", 1)->get();

            $loading_assigned_booking_containers = 0;
            $loading_finished_booking_containers = 0;

            $unloading_daily_booking_containers =  DailyBookingContainer::whereDate("created_at", now())->where("superagent_id", auth()->guard("superagent")->id())->where("booking_container_status", 2)->get();

            $unloading_assigned_booking_containers = 0;
            $unloading_finished_booking_containers = 0;

            foreach ($specification_daily_booking_containers as $specification_daily) {

                $booking_container_agent = BookingContainerAgent::whereDate("created_at", now())->where([["booking_container_id", "=", $specification_daily->booking_container_id]])->first();

                if ($booking_container_agent) {

                    //assigned specification tasks

                    $specification_assigned_booking_containers += 1;
                }
                if ($specification_daily->booking_container_status != $specification_daily?->booking_container?->status) {

                    //fisined specification tasks

                    $specification_finished_booking_containers += 1;
                }
            }

            foreach ($loading_daily_booking_containers as $loading_daily) {

                $booking_container_agent = BookingContainerAgent::whereDate("created_at", now())->where([["booking_container_id", "=", $loading_daily->booking_container_id]])->first();

                if ($booking_container_agent) {

                    //assigned loading tasks

                    $loading_assigned_booking_containers += 1;
                }
                if ($loading_daily->booking_container_status != $loading_daily?->booking_container?->status) {

                    //fisined loading tasks

                    $loading_finished_booking_containers += 1;
                }
            }


            foreach ($unloading_daily_booking_containers as $unloading_daily) {

                $booking_container_agent = BookingContainerAgent::whereDate("created_at", now())->where([["booking_container_id", "=", $unloading_daily->booking_container_id]])->first();

                if ($booking_container_agent) {

                    //assigned unloading tasks

                    $unloading_assigned_booking_containers += 1;
                }
                if ($unloading_daily->booking_container_status != $unloading_daily?->booking_container?->status) {

                    //fisined unloading tasks

                    $unloading_finished_booking_containers += 1;
                }
            }
            $shipping_agent_ids = Booking::whereHas("shippingAgent", function ($q) {
                $q->whereHas("bookingContainers", function ($qc) {
                    $qc->where("status", 0)->orWhere("status", 1);
                });
            })->orderBy("id", "desc")->get()->pluck("shipping_agent_id")->toArray();


            $shipping_agents = shippingAgent::whereIn("id", $shipping_agent_ids)->get();

            $data["specification"]["daily"] = count($specification_daily_booking_containers);
            $data["specification"]["assigned"] = $specification_assigned_booking_containers;
            $data["specification"]["finished"] = $specification_finished_booking_containers;

            $data["loading"]["daily"] = count($loading_daily_booking_containers);
            $data["loading"]["assigned"] = $loading_assigned_booking_containers;
            $data["loading"]["finished"] = $loading_finished_booking_containers;

            $data["unloading"]["daily"] = count($unloading_daily_booking_containers);
            $data["unloading"]["assigned"] = $unloading_assigned_booking_containers;
            $data["unloading"]["finished"] = $unloading_finished_booking_containers;
            $data["shipping_agents"] = ShippingAgentResource::collection($shipping_agents);

            //response

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $ex) {


            return $this->returnError(500, $ex->getMessage());
        }
    }
}
