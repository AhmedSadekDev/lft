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
            $agent_ids = array_values(array_unique(array_filter($request->agent_ids ?? [], function ($id) {
                return $id !== null && Agent::find($id) !== null;
            })));

            if (empty($agent_ids)) {
                return $this->returnError(422, 'يجب اختيار مندوب واحد على الأقل');
            }

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
                : ($request->filled('booking_id') ? [$request->booking_id] : []);

            $containerIds = $request->input('booking_container_ids', $request->input('booking_container_id'));
            $containerIds = is_array($containerIds) ? array_filter($containerIds) : ($containerIds ? [$containerIds] : []);

            // Filter out null and invalid agent IDs
            $agent_ids = array_values(array_unique(array_filter($request->agent_ids ?? [], function ($id) {
                return $id !== null && Agent::find($id) !== null;
            })));

            if (empty($agent_ids)) {
                return $this->returnError(422, 'يجب اختيار مندوب واحد على الأقل');
            }

            $specScope = function ($q) {
                $q->where('superagent_specification_approved', 0)
                    ->where(function ($query) {
                        $query->where('status', 0)
                            ->orWhere(function ($q2) {
                                $q2->where('status', 1)
                                    ->where('superagent_specification_approved', 0);
                            });
                    });
            };

            if (! empty($containerIds)) {
                $booking_containers = BookingContainer::whereIn('id', $containerIds)
                    ->where($specScope)
                    ->orderBy('id')
                    ->get();
            } elseif (! empty($booking_ids)) {
                $bookings = Booking::whereIn('id', $booking_ids)->get();
                if ($bookings->isEmpty()) {
                    return $this->returnError(404, __('Booking not found'));
                }
                $booking_containers = BookingContainer::whereIn('booking_id', $bookings->pluck('id'))
                    ->where($specScope)
                    ->orderBy('id')
                    ->get();
            } else {
                return $this->returnError(422, 'يجب تحديد طلب أو حاويات');
            }

            if ($booking_containers->isEmpty()) {
                return $this->returnError(404, 'لا توجد حاويات في مرحلة التخصيص للتكليف');
            }

            $agents = Agent::whereIn("id", $agent_ids)->get();

            DB::transaction(function () use ($booking_containers, $agent_ids) {
                foreach ($booking_containers as $booking_container) {
                    app(\App\Services\ContainerStageService::class)->assign($booking_container->id, $agent_ids, 0);
                }
            });

            // Notify each agent once per assigned container
            foreach ($agents as $agent) {
                $title = __('new_notification');
                $text = __('booking_assigned', [
                    'superagent' => $superagent->name,
                    'agent' => $agent->name
                ]);

                foreach ($booking_containers as $booking_container) {
                    SaveNotification::create(
                        $title,
                        $text,
                        $agent->id,
                        Agent::class,
                        AppNotification::specific,
                        $booking_container->id,
                        0
                    );

                    if ($agent->device_token) {
                        $notificationData = [
                            'booking_id' => $booking_container->booking_id,
                            'booking_container_id' => $booking_container->id,
                            'type_id' => 0,
                            'type' => 'specification',
                            'type_action' => 'specification',
                            'action_type' => 'specification',
                        ];
                        SendNotification::send($agent->device_token, $title, $text, $notificationData);
                    }
                }
            }

            return $this->returnResponseSuccessMessage(__('alerts.success'));
        } catch (\Exception $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : ($ex instanceof \Illuminate\Validation\ValidationException ? 422 : 500), $ex->getMessage());
        }
    }

    public function approve(Request $request)
    {
        $rules = [
            'booking_container_id'  => 'nullable|integer|exists:booking_containers,id',
            'booking_container_ids' => 'nullable|array|min:1',
            'booking_container_ids.*' => 'integer|exists:booking_containers,id',
            'type_id' => 'required|in:0,1,2',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->returnError(500, $validator->errors()->first());
        }

        $containerIds = $request->get('booking_container_ids');
        $containerIds = is_array($containerIds) ? array_values(array_unique(array_filter($containerIds))) : [];
        if (empty($containerIds) && $request->filled('booking_container_id')) {
            $containerIds = [(int) $request->booking_container_id];
        }

        if (empty($containerIds)) {
            return $this->returnError(400, 'يجب تحديد حاوية واحدة على الأقل (booking_container_id أو booking_container_ids)');
        }

        $containers = BookingContainer::whereIn('id', $containerIds)->orderBy('id')->get();
        if ($containers->count() !== count($containerIds)) {
            return $this->returnError(500, __('main.not_found'));
        }

        $superagent = auth()->guard("superagent")->user();
        $typeId = (int) $request->type_id;
        $stageService = app(\App\Services\ContainerStageService::class);

        // وحدة العمل = الحاوية (المحددة فقط)، مش كل البوكينج
        $agentIds = BookingContainerAgent::whereIn('booking_container_id', $containerIds)
            ->where('stage_type', $typeId)
            ->pluck('agent_id')
            ->unique()
            ->all();

        $changed = DB::transaction(function () use ($containerIds, $stageService, $superagent, $typeId) {
            $changed = false;
            foreach ($containerIds as $id) {
                $changed = $stageService->approve($id, $typeId, $superagent->id) || $changed;
            }

            return $changed;
        });

        if (!$changed) {
            return $this->returnAllData('', __('alerts.success'));
        }

        $first = $containers->first();
        $agents = Agent::whereIn('id', $agentIds)->get();
        foreach ($agents as $agent) {
            $title = __('new_notification');
            if ($typeId === 0) {
                $text = __('booking_specification_approved', [
                    'booking_number' => $first->booking->booking_number ?? '',
                ]);
            } elseif ($typeId === 1) {
                $text = __('booking_loading_approved', [
                    'container_no' => $first->container_no ?? '',
                ]);
            } else {
                $text = __('booking_unloading_approved', [
                    'container_no' => $first->container_no ?? '',
                ]);
            }

            SaveNotification::create(
                $title,
                $text,
                $agent->id,
                Agent::class,
                AppNotification::specific,
                $first->id,
                $typeId
            );

            if ($agent->device_token) {
                $notificationData = [
                    'booking_id' => $first->booking_id,
                    'booking_container_id' => $first->id,
                    'booking_container_ids' => $containerIds,
                    'type_id' => $typeId,
                    'type' => $typeId === 0 ? 'specification' : ($typeId === 1 ? 'loading' : 'unloading'),
                    'type_action' => $typeId === 0 ? 'specification' : ($typeId === 1 ? 'loading' : 'unloading'),
                    'action_type' => $typeId === 0 ? 'specification' : ($typeId === 1 ? 'loading' : 'unloading'),
                ];
                SendNotification::send($agent->device_token, $title, $text, $notificationData);
            }
        }

        // إيميل الحالة يُرسل عبر send_stage_email فقط — لا يُرسل مع الاعتماد.
        return $this->returnAllData("", __('alerts.success'));
    }

    /**
     * Send stage status email without approving or moving the container.
     */
    public function send_stage_email(Request $request)
    {
        try {
            [$containers, $typeId] = $this->resolveStageNotifyRequest($request);
            $message = $this->stageNotifyMessage($containers, $typeId);
            $sent = 0;

            foreach ($containers as $container) {
                $this->assertStageReadyForNotify($container, $typeId);
                $container->loadMissing(['booking.employee', 'booking.company']);
                if ($container->booking?->employee) {
                    Notification::send($container->booking->employee, new ConatinerStatus($container, $message));
                    $sent++;
                }
                if ($container->booking?->company) {
                    Notification::send($container->booking->company, new ConatinerStatus($container, $message));
                    $sent++;
                }
            }

            abort_if($sent === 0, 422, 'لا يوجد بريد للمستلمين');

            return $this->returnAllData(['sent' => $sent], __('alerts.success'));
        } catch (\Throwable $e) {
            return $this->returnError(
                $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $e->getStatusCode() : ($e instanceof \Illuminate\Validation\ValidationException ? 422 : 500),
                $e instanceof \Illuminate\Validation\ValidationException ? $e->validator->errors()->first() : $e->getMessage()
            );
        }
    }

    /**
     * Build WhatsApp share URLs without approving or moving the container.
     */
    public function send_stage_whatsapp(Request $request)
    {
        try {
            [$containers, $typeId] = $this->resolveStageNotifyRequest($request);
            $message = $this->stageNotifyMessage($containers, $typeId);
            $links = [];

            foreach ($containers as $container) {
                $this->assertStageReadyForNotify($container, $typeId);
                $container->loadMissing(['booking.company', 'booking.employee']);
                $phone = $this->stageWhatsappPhone($container, $typeId);
                abort_if(! $phone, 422, 'لا يوجد رقم واتساب للمستلمين');
                $links[] = [
                    'booking_container_id' => $container->id,
                    'phone' => $phone,
                    'whatsapp_url' => 'https://wa.me/'.$phone.'?text='.rawurlencode($this->stageWhatsappText($container, $message)),
                ];
            }

            return $this->returnAllData(['links' => $links], __('alerts.success'));
        } catch (\Throwable $e) {
            return $this->returnError(
                $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $e->getStatusCode() : ($e instanceof \Illuminate\Validation\ValidationException ? 422 : 500),
                $e instanceof \Illuminate\Validation\ValidationException ? $e->validator->errors()->first() : $e->getMessage()
            );
        }
    }

    private function resolveStageNotifyRequest(Request $request): array
    {
        $data = $request->validate([
            'booking_container_id' => 'nullable|integer|exists:booking_containers,id',
            'booking_container_ids' => 'nullable|array|min:1',
            'booking_container_ids.*' => 'integer|exists:booking_containers,id',
            'type_id' => 'required|in:0,1,2',
        ]);

        $containerIds = is_array($data['booking_container_ids'] ?? null)
            ? array_values(array_unique(array_filter($data['booking_container_ids'])))
            : [];
        if (empty($containerIds) && ! empty($data['booking_container_id'])) {
            $containerIds = [(int) $data['booking_container_id']];
        }
        abort_if(empty($containerIds), 400, 'يجب تحديد حاوية واحدة على الأقل (booking_container_id أو booking_container_ids)');

        $containers = BookingContainer::whereIn('id', $containerIds)->orderBy('id')->get();
        abort_unless($containers->count() === count($containerIds), 404, __('main.not_found'));

        return [$containers, (int) $data['type_id']];
    }

    private function assertStageReadyForNotify(BookingContainer $container, int $typeId): void
    {
        $name = \App\Services\ContainerStageService::NAMES[$typeId];
        abort_if((int) $container->{'superagent_'.$name.'_approved'} === 1, 409, 'المرحلة معتمدة بالفعل؛ استخدم زر التأكيد فقط للنقل');
        app(\App\Services\ContainerStageService::class)->assertAvailable($container, $typeId);
        // الإيميل/الواتساب للاشعار فقط — يُسمح في أي وقت قبل الاعتماد حتى لو المندوب لم يُنه المرحلة.
    }

    private function stageNotifyMessage($containers, int $typeId): string
    {
        $first = $containers->first();
        $count = $containers->count();
        if ($typeId === 0) {
            return $count > 1
                ? 'تحديث تخصيص '.$count.' حاوية'
                : 'تم تخصيص حاوية الطلب '.($first->booking_id ?? '');
        }
        if ($typeId === 1) {
            return $count > 1
                ? 'تحديث تحميل '.$count.' حاوية'
                : 'تم تحميل حاوية رقم '.($first->container_no ?? $first->id);
        }

        return $count > 1
            ? 'تحديث تعتيق '.$count.' حاوية'
            : 'تم تعتيق حاوية رقم '.($first->container_no ?? $first->id);
    }

    private function stageWhatsappPhone(BookingContainer $container, int $typeId): ?string
    {
        $candidates = [
            $container->booking?->company?->phone,
            $container->booking?->employee?->phone,
        ];
        $agentIds = BookingContainerAgent::where('booking_container_id', $container->id)
            ->where('stage_type', $typeId)
            ->pluck('agent_id');
        foreach (Agent::whereIn('id', $agentIds)->pluck('phone') as $raw) {
            $candidates[] = $raw;
        }
        foreach ($candidates as $raw) {
            $phone = $this->normalizeEgyptWhatsappPhone($raw);
            if ($phone !== null) {
                return $phone;
            }
        }

        return null;
    }

    /**
     * يحوّل الرقم لصيغة واتساب دولية بكود مصر 20.
     * أمثلة: 01012345678 → 201012345678 ، +20 10... → 2010...
     */
    private function normalizeEgyptWhatsappPhone(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0')) {
            $digits = '20'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '20')) {
            $digits = '20'.$digits;
        }

        return $digits !== '' ? $digits : null;
    }

    private function stageWhatsappText(BookingContainer $container, string $message): string
    {
        $booking = $container->booking;

        return implode("\n", array_filter([
            $message,
            'رقم الحجز: '.($booking->booking_number ?? '-'),
            'رقم الحاوية: '.($container->container_no ?? '-'),
            'الشركة: '.($booking?->company?->name ?? '-'),
            $booking?->booking_number
                ? 'التتبع: https://leaderfortrans.com/book/?track='.urlencode($booking->booking_number)
                : null,
        ]));
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
