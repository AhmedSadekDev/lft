<?php

namespace App\Http\Controllers\Api\Superagent;

use App\Models\Agent;
use App\Models\Booking;
use App\Models\AgentExpense;
use App\Models\BookingPaper;
use Illuminate\Http\Request;
use App\Models\AppNotification;
use App\Models\BookingContainer;
use App\Services\SaveNotification;
use App\Services\SendNotification;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\BookingContainerAgent;
use App\Models\DailyBookingContainer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ConatinerStatus;
use App\Http\Requests\Api\Superagent\AgentRequest;
use App\Http\Resources\Api\Superagent\AgentResource;
use App\Http\Requests\Api\Superagent\BookingAgentRequest;
use App\Http\Resources\Api\Superagent\SimpleBookingContainerResource;


class AgentController extends Controller
{
    public function fetch_agents()
    {
        try {

            $agents = Agent::orderBy("id", "desc")->ofFilter()->get();


            $data = AgentResource::collection($agents);

            //response

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $ex) {


            return $this->returnError(500, $ex->getMessage());
        }
    }

    public function assign_agents(Request $request)
    {
        try {
            $superagent = auth()->guard("superagent")->user();
            $booking_container = BookingContainer::find($request->booking_container_id);

            if (!$booking_container) {
                return $this->returnError(404, __('Booking container not found'));
            }

            // Filter out null and invalid agent IDs
            $agent_ids = array_filter($request->agent_ids ?? [], function ($id) {
                return $id !== null && Agent::find($id) !== null;
            });

            // Delete existing records for the given booking container and agent IDs
            BookingContainerAgent::where('booking_container_id', $request->booking_container_id)->delete();

            // Re-create BookingContainerAgent records
            if (count($agent_ids)) {
                foreach ($agent_ids as $agentId) {
                    BookingContainerAgent::create([
                        'booking_container_id' => $request->booking_container_id,
                        'agent_id' => $agentId,
                        'booking_container_status' => $booking_container->status,
                        'superagent_specification_approved' => $booking_container->superagent_specification_approved,
                        'superagent_loading_approved' => $booking_container->superagent_loading_approved,
                        'superagent_unloading_approved' => $booking_container->superagent_unloading_approved,
                    ]);
                }
            }

            // Prepare data for the response
            $data = new SimpleBookingContainerResource($booking_container);

            // Notify each agent
            foreach ($booking_container->agents as $agent) {
                $title = __('new_notification');
                $text = __('booking_container_assigned', [
                    'superagent' => $superagent->name,
                    'agent' => $agent->name
                ]);

                SaveNotification::create($title, $text, $agent->id, Agent::class, AppNotification::specific);

                if ($agent->device_token) {
                    SendNotification::send($agent->device_token, $title, $text);
                }
            }

            // Response
            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $ex) {
            return $this->returnError(500, $ex->getMessage());
        }
    }
    public function assign_specification_booking(BookingAgentRequest $request)
    {
        try {

            $superagent = auth()->guard("superagent")->user();
            $booking = Booking::whereId($request->booking_id)->first();

            $booking_containers = $booking->bookingContainers()->where("booking_containers.status", 0)->get();

            $agents = Agent::whereIn("id", $request->agent_ids)->get();

            foreach ($booking_containers as $booking_container) {
                $booking_container->agents()->wherePivot('booking_container_status', '=', $booking_container->status)->detach($request->agent_ids);
                $booking_container->agents()->attach($request->agent_ids, ["booking_container_status" => $booking_container->status]);
            }
            foreach ($agents as $agent) {
                $title = __('new_notification');
                $text = __('booking_assigned', [
                    'superagent' => $superagent->name,
                    'agent' => $agent->name
                ]);

                SaveNotification::create($title, $text, $agent->id, Agent::class, AppNotification::specific);
                SendNotification::send($agent->device_token ?? "", $title, $text);
            }
            //response

            return $this->returnResponseSuccessMessage(__('alerts.success'));
        } catch (\Exception $ex) {


            return $this->returnError(500, $ex->getMessage());
        }
    }

    public function approve(Request $request)
    {
        $rules = [
            'booking_container_id'  => 'required',
            'type_id' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->returnError(500, $validator->errors()->first());
        }

        $container = BookingContainer::find($request->booking_container_id);


        if (!$container) {
            return $this->returnError(500, __('main.not_found'));
        }


        $superagent = auth()->guard("superagent")->user();


        if ($request->type_id == 0) {

            foreach ($container->booking->bookingContainers as $con) {

                $con->update([
                    'superagent_specification_approved'   => 1,
                    'status' => 1
                ]);
            }

            $containerIds = $container->booking->bookingContainers->pluck('id')->toArray();
            $bookingContainerAgents = BookingContainerAgent::whereIn('booking_container_id', $containerIds)->get();
            $bookingContainerDaily = DailyBookingContainer::whereIn('booking_container_id', $containerIds)->get();


            foreach ($bookingContainerAgents as $item) {
                $item->update([
                    'booking_container_status' => 1,
                    'superagent_specification_approved'   => 1,
                ]);
            }

            foreach ($bookingContainerDaily as $item) {
                $item->update([
                    'booking_container_status' => 1,
                    'superagent_specification_approved'   => 1,
                ]);
            }
            $message = 'تم تخصيص حاويات الطلب ' . $container->booking_id;

        } elseif ($request->type_id == 1) {

            $container->update([
                'superagent_loading_approved'   => 1,
                'status' => 2
            ]);

            $containerIds = $container->booking->bookingContainers->pluck('id')->toArray();
            $bookingContainerAgent = BookingContainerAgent::where('booking_container_id', $containerIds)->first();
            $bookingContainerDaily = DailyBookingContainer::where('booking_container_id', $containerIds)->first();

            $bookingContainerAgent->update([
                'booking_container_status' => 2,
                'superagent_loading_approved' => 1
            ]);

            $bookingContainerDaily->update([
                'booking_container_status' => 2,
                'superagent_loading_approved' => 1
            ]);
            $message = 'تم تحميل حاوية رقم ' . $container->container_no;

        } elseif ($request->type_id == 2) {

            $container->update([
                'superagent_unloading_approved'   => 1,
                'status' => 3
            ]);

            $containerIds = $container->booking->bookingContainers->pluck('id')->toArray();
            $bookingContainerAgent = BookingContainerAgent::where('booking_container_id', $containerIds)->first();
            $bookingContainerDaily = DailyBookingContainer::where('booking_container_id', $containerIds)->first();

            $bookingContainerAgent->update([
                'booking_container_status' => 2,
                'superagent_unloading_approved' => 1
            ]);

            $bookingContainerDaily->update([
                'booking_container_status' => 2,
                'superagent_unloading_approved' => 1
            ]);

            $message = 'تم تعتيق حاوية رقم ' . $container->container_no;
        }

        Notification::send($container->booking->company, new ConatinerStatus($container, $message));


        return $this->returnAllData("", __('alerts.success'));
    }


    public function expenses(Request $request)
    {
        $rules = [
            'booking_container_id'  => 'required',
            'type_id' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->returnError(500, $validator->errors()->first());
        }


        $xpenses = AgentExpense::where('booking_container_id', $request->booking_container_id)->where('type_id', $request->type_id)->get()->map(function ($ex) {
            return [
                'id' => $ex->id,
                'agent_id' => $ex->agent_id ?? 0,
                'agent_name' => $ex->agent ? $ex->agent->name : '',
                'image' => $ex->image,
                'value' => $ex->value
            ];
        });

        $papers = BookingPaper::where('booking_container_id', $request->booking_container_id)->where('type', $request->type_id)->get()->map(function ($paper) {
            return [
                'id'    => $paper->id,
                'image' => $paper->image->image,
                'agent_id' => $paper->agent_id ?? 0,
                'agent_name' => $paper->agent ? $paper->agent->name : '',
            ];
        });

        $agent =

            $data = [
                'expenses'  => $xpenses,
                'papers' => $papers
            ];

        return $this->returnAllData($data, __('alerts.success'));
    }


    public function changeStatus(Request $request)
    {
        $rules = [
            'booking_container_id'  => 'required|exists:booking_containers,id',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->returnError(500, $validator->errors()->first());
        }


        $container =  BookingContainer::find($request->booking_container_id);

        // update the container status
        if ($container->status == 1) {
            $container->update([
                'status'  => 0
            ]);
        } elseif ($container->status == 2) {
            $container->update([
                'status'  => 1
            ]);
        }

        // update the container at the agent status
        $agentContainer = BookingContainerAgent::where("booking_container_id", $request->booking_container_id)->first();

        if ($agentContainer) {
            if ($agentContainer->booking_container_status == 1) {
                $agentContainer->update([
                    'booking_container_status'  => 0
                ]);
            } elseif ($agentContainer->booking_container_status == 2) {
                $agentContainer->update([
                    'booking_container_status'  => 1
                ]);
            }
        }

        // update the daily container at the super agent
        $dailyContainer = DailyBookingContainer::where("booking_container_id", $request->booking_container_id)->first();

        if ($dailyContainer) {
            if ($dailyContainer->booking_container_status == 1) {
                $dailyContainer->update([
                    'booking_container_status'  => 0
                ]);
            } elseif ($dailyContainer->booking_container_status == 2) {
                $dailyContainer->update([
                    'booking_container_status'  => 1
                ]);
            }
        }


        return $this->returnAllData('', __('alerts.success'));
    }
}
