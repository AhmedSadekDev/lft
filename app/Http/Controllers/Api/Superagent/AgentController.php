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
    private function getContainerNotificationTypeId(BookingContainer $container): int
    {
        if (! $container->superagent_specification_approved) {
            return 0;
        }

        if (! $container->superagent_loading_approved) {
            return 1;
        }

        return 2;
    }

    private function getContainerNotificationType(BookingContainer $container): string
    {
        return match ($this->getContainerNotificationTypeId($container)) {
            0 => 'specification',
            1 => 'loading',
            2 => 'unloading',
        };
    }

    public function fetch_agents()
    {
        try {

            $agents = Agent::orderBy("id", "desc")->ofFilter()->get();


            $data = AgentResource::collection($agents);

            //response

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $ex) {


            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : ($ex instanceof \Illuminate\Validation\ValidationException ? 422 : 500), $ex->getMessage());
        }
    }

    public function assign_agents(Request $request)
    {
        try {
            $request->validate(['type_id' => 'sometimes|integer|in:0,1,2', 'agent_ids' => 'present|array', 'agent_ids.*' => 'required|integer|exists:agents,id']);
            $superagent = auth()->guard("superagent")->user();

            $request->validate([
                'booking_container_ids' => 'sometimes|array|min:1',
                'booking_container_ids.*' => 'required|integer|exists:booking_containers,id',
                'booking_ids' => 'sometimes|array|min:1',
                'booking_ids.*' => 'required|integer|exists:bookings,id',
            ]);
            // Keep the historical singular parameter's booking-id meaning; new clients use explicit arrays.
            if ($request->has('booking_container_ids')) {
                $booking_containers = BookingContainer::whereIn('id', $request->booking_container_ids)->orderBy('id')->get();
            } else {
                $ids = $request->input('booking_ids', $request->booking_container_id);
                $ids = is_array($ids) ? $ids : [$ids];
                $booking_containers = BookingContainer::whereIn('booking_id', $ids)->orderBy('id')->get();
            }

            if ($booking_containers->isEmpty()) {
                return $this->returnError(404, __('Booking container not found'));
            }

            // Filter out null and invalid agent IDs
            $agent_ids = array_filter($request->agent_ids ?? [], function ($id) {
                return $id !== null && Agent::find($id) !== null;
            });

            $processed_containers = [];

            $assignmentTypes = DB::transaction(function () use ($booking_containers, $agent_ids, $request) {
                $types = [];
                foreach ($booking_containers as $container) {
                    $types[$container->id] = app(\App\Services\ContainerStageService::class)->assign(
                        $container->id, $agent_ids, $request->filled('type_id') ? (int) $request->type_id : null
                    );
                }
                return $types;
            });
            foreach ($booking_containers as $booking_container) {
                $assignmentType = $assignmentTypes[$booking_container->id];
                $booking_container->load(['agents' => fn ($q) => $q->where('booking_container_agents.stage_type', $assignmentType)]);

                // Notify each agent
                foreach ($booking_container->agents as $agent) {
                    $title = __('new_notification');
                    $text = __('booking_container_assigned', [
                        'superagent' => $superagent->name,
                        'agent' => $agent->name
                    ]);

                    $notificationTypeId = $assignmentType;
                    $notificationType = \App\Services\ContainerStageService::NAMES[$assignmentType];

                    SaveNotification::create(
                        $title,
                        $text,
                        $agent->id,
                        Agent::class,
                        AppNotification::specific,
                        $booking_container->id,
                        $notificationTypeId
                    );

                    if ($agent->device_token) {
                        $notificationData = [
                            'booking_id' => $booking_container->booking_id,
                            'booking_container_id' => $booking_container->id,
                            'type_id' => $notificationTypeId,
                            'type' => $notificationType,
                            'type_action' => $notificationType,
                            'action_type' => $notificationType,
                        ];
                        SendNotification::send($agent->device_token, $title, $text, $notificationData);
                    }
                }

                $processed_containers[] = $booking_container;
            }

            // Prepare data for the response
            $data = SimpleBookingContainerResource::collection($processed_containers);

            // Response
            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : ($ex instanceof \Illuminate\Validation\ValidationException ? 422 : 500), $ex->getMessage());
        }
    }
    public function assign_specification_booking(BookingAgentRequest $request)
    {
        try {

            $superagent = auth()->guard("superagent")->user();

            // Get booking_ids as array
            $booking_ids = is_array($request->booking_id)
                ? $request->booking_id
                : [$request->booking_id];

            // Validate all booking IDs exist
            $bookings = Booking::whereIn('id', $booking_ids)->get();

            if ($bookings->isEmpty()) {
                return $this->returnError(404, __('Booking not found'));
            }

            // Filter out null and invalid agent IDs
            $agent_ids = array_filter($request->agent_ids ?? [], function ($id) {
                return $id !== null && Agent::find($id) !== null;
            });

            $agents = Agent::whereIn("id", $agent_ids)->get();

            // Process each booking
            foreach ($bookings as $booking) {
                $booking_containers = $booking->bookingContainers()->where("booking_containers.status", 0)->get();

                foreach ($booking_containers as $booking_container) {
                    app(\App\Services\ContainerStageService::class)->assign($booking_container->id, $agent_ids, 0);
                }
            }

            // Notify each agent
            foreach ($agents as $agent) {
                $title = __('new_notification');
                $text = __('booking_assigned', [
                    'superagent' => $superagent->name,
                    'agent' => $agent->name
                ]);

                // Save and send one notification for each booking.
                foreach ($bookings as $booking) {
                    $notificationContainer = $booking->bookingContainers()
                        ->where('booking_containers.status', 0)
                        ->first();

                    SaveNotification::create(
                        $title,
                        $text,
                        $agent->id,
                        Agent::class,
                        AppNotification::specific,
                        $notificationContainer?->id,
                        0
                    );

                    if ($agent->device_token) {
                        $notificationData = [
                            'booking_id' => $booking->id,
                            'booking_container_id' => $notificationContainer?->id ?? '',
                            'type_id' => 0,
                            'type' => 'specification',
                            'type_action' => 'specification',
                            'action_type' => 'specification',
                        ];
                        SendNotification::send($agent->device_token, $title, $text, $notificationData);
                    }
                }
            }
            //response

            return $this->returnResponseSuccessMessage(__('alerts.success'));
        } catch (\Exception $ex) {


            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : ($ex instanceof \Illuminate\Validation\ValidationException ? 422 : 500), $ex->getMessage());
        }
    }

    public function approve(Request $request)
    {
        $rules = [
            'booking_container_id'  => 'required',
            'type_id' => 'required|in:0,1,2'
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

        $message = '';

        if ($request->type_id == 0) {
            $containerIds = $container->booking->bookingContainers->pluck('id')->sort()->values()->all();
            $stageService = app(\App\Services\ContainerStageService::class);
            $agentIds = BookingContainerAgent::whereIn('booking_container_id', $containerIds)->where('stage_type', 0)->pluck('agent_id')->unique()->all();
            $changed = DB::transaction(function () use ($containerIds, $stageService) {
                $changed = false;
                foreach ($containerIds as $id) {
                    $changed = $stageService->approve($id, 0) || $changed;
                }
                return $changed;
            });
            if (!$changed) { return $this->returnAllData('', __('alerts.success')); }
            $message = 'تم تخصيص حاويات الطلب ' . $container->booking_id;

            // Send Firebase notifications to agents
            $agents = Agent::whereIn('id', $agentIds)->get();
            foreach ($agents as $agent) {
                $title = __('new_notification');
                $text = __('booking_specification_approved', [
                    'booking_number' => $container->booking->booking_number ?? ''
                ]);

                SaveNotification::create(
                    $title,
                    $text,
                    $agent->id,
                    Agent::class,
                    AppNotification::specific,
                    $container->id,
                    0
                );

                if ($agent->device_token) {
                    $notificationData = [
                        'booking_id' => $container->booking_id,
                        'booking_container_id' => $container->id,
                        'type_id' => 0,
                        'type' => 'specification',
                        'type_action' => 'specification',
                        'action_type' => 'specification',
                    ];
                    SendNotification::send($agent->device_token, $title, $text, $notificationData);
                }
            }
        } elseif ($request->type_id == 1) {
            $stageService = app(\App\Services\ContainerStageService::class);
            $agentIds = $stageService->assignments($container->id, 1)->pluck('agent_id')->unique()->all();
            if (!$stageService->approve($container->id, 1)) { return $this->returnAllData('', __('alerts.success')); }
            $container->refresh();
            $message = 'تم تحميل حاوية رقم ' . $container->container_no;

            // Send Firebase notifications to agents
            $agents = Agent::whereIn('id', $agentIds)->get();
            foreach ($agents as $agent) {
                $title = __('new_notification');
                $text = __('booking_loading_approved', [
                    'container_no' => $container->container_no ?? ''
                ]);

                SaveNotification::create(
                    $title,
                    $text,
                    $agent->id,
                    Agent::class,
                    AppNotification::specific,
                    $container->id,
                    1
                );

                if ($agent->device_token) {
                    $notificationData = [
                        'booking_id' => $container->booking_id,
                        'booking_container_id' => $container->id,
                        'type_id' => 1,
                        'type' => 'loading',
                        'type_action' => 'loading',
                        'action_type' => 'loading',
                    ];
                    SendNotification::send($agent->device_token, $title, $text, $notificationData);
                }
            }
        } elseif ($request->type_id == 2) {
            $stageService = app(\App\Services\ContainerStageService::class);
            $agentIds = $stageService->assignments($container->id, 2)->pluck('agent_id')->unique()->all();
            if (!$stageService->approve($container->id, 2)) { return $this->returnAllData('', __('alerts.success')); }
            $container->refresh();
            $message = 'تم تعتيق حاوية رقم ' . $container->container_no;

            // Send Firebase notifications to agents
            $agents = Agent::whereIn('id', $agentIds)->get();
            foreach ($agents as $agent) {
                $title = __('new_notification');
                $text = __('booking_unloading_approved', [
                    'container_no' => $container->container_no ?? ''
                ]);

                SaveNotification::create(
                    $title,
                    $text,
                    $agent->id,
                    Agent::class,
                    AppNotification::specific,
                    $container->id,
                    2
                );

                if ($agent->device_token) {
                    $notificationData = [
                        'booking_id' => $container->booking_id,
                        'booking_container_id' => $container->id,
                        'type_id' => 2,
                        'type' => 'unloading',
                        'type_action' => 'unloading',
                        'action_type' => 'unloading',
                    ];
                    SendNotification::send($agent->device_token, $title, $text, $notificationData);
                }
            }
        }

        // إرسال الإشعار للموظف والشركة
        if ($container->booking->employee) {
            Notification::send($container->booking->employee, new ConatinerStatus($container, $message));
        }
        if ($container->booking->company) {
            Notification::send($container->booking->company, new ConatinerStatus($container, $message));
        }


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
        if ($request->type_id == 0 || $request->type_id == 1) {
            $types = [$request->type_id];
        } else {
            $types = [4, 5];
        }

        $papers = BookingPaper::where('booking_container_id', $request->booking_container_id)
            ->whereIn('type', $types)
            ->get()
            ->map(function ($paper) {
                return [
                    'id'         => $paper->id,
                    'image'      => $paper->image->image,
                    'agent_id'   => $paper->agent_id ?? 0,
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

        app(\App\Services\ContainerStageService::class)->rewind($container->id, (int) $container->status - 1);

        return $this->returnAllData('', __('alerts.success'));
    }
}
