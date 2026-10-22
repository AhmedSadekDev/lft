<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Agent\ReeferTripListResource;
use App\Models\DeliveryPolicy;
use App\Models\ReeferTripLeg;
use App\Services\ReeferTripTrackingService;
use App\Support\ReeferTripStages;
use App\Traits\HandlesAgentImageUploads;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReeferTripController extends Controller
{
    use HandlesAgentImageUploads;

    private const DOC_FOLDER = 'reefer_trip_docs';

    public function __construct(private ReeferTripTrackingService $tracking)
    {
    }

    public function fetch_reefer_trip_list(Request $request)
    {
        try {
            $agent = auth()->guard('agent')->user();
            $perPage = (int) $request->get('per_page', 20);
            $page = (int) $request->get('page', 1);

            $paginator = $this->tracking->paginateForAgent($agent, max(1, min($perPage, 100)), max(1, $page));
            $data = ReeferTripListResource::collection($paginator->items());

            return response()->json([
                'status' => true,
                'errNum' => '0000',
                'message' => __('alerts.success'),
                'data' => $data,
                'pagination' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'total_pages' => $paginator->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return $this->returnError(500, $e->getMessage());
        }
    }

    public function reefer_trip_details(Request $request)
    {
        try {
            $request->validate([
                'delivery_policy_id' => 'required|exists:delivery_policies,id',
            ]);

            $agent = auth()->guard('agent')->user();
            $policy = DeliveryPolicy::with([
                'driver',
                'car',
                'money_transfer',
                'booking_containers.loading',
                'booking_containers.booking',
                'reeferTripLegs.images',
                'images',
            ])->findOrFail($request->delivery_policy_id);

            $this->tracking->assertAgentCanAccess($policy, $agent);

            $first = $policy->booking_containers->first();

            return $this->returnAllData([
                'id' => $policy->id,
                'driver_name' => $policy->driver?->name ?? '',
                'destination' => $first?->loading?->title ?? '',
                'custody_egp' => (float) ($policy->money_transfer?->value ?? 0),
                'custody_usd' => $policy->custody_usd !== null ? (float) $policy->custody_usd : null,
                'custody_usd_is_record_only' => true,
                'booking_number' => $first?->booking?->booking_number ?? '',
                'container_no' => $first?->container_no ?? '',
                'timeline' => $this->tracking->buildTimeline($policy),
                'policy_documents' => $policy->images->map(fn ($img) => [
                    'id' => $img->id,
                    'url' => $img->image,
                ])->values()->all(),
            ], __('alerts.success'));
        } catch (ValidationException $e) {
            return $this->returnError(403, $e->errors()['delivery_policy_id'][0] ?? __('main.not_allowed'));
        } catch (\Exception $e) {
            return $this->returnError(500, $e->getMessage());
        }
    }

    public function save_reefer_trip_stage(Request $request)
    {
        try {
            $request->validate([
                'delivery_policy_id' => 'required|exists:delivery_policies,id',
                'stage' => 'required|integer|min:1|max:11',
                'occurred_at' => 'nullable|date',
                'notes' => 'nullable|string|max:5000',
            ]);

            $agent = auth()->guard('agent')->user();
            $policy = DeliveryPolicy::with(['money_transfer', 'booking_containers.booking'])
                ->findOrFail($request->delivery_policy_id);

            $leg = $this->tracking->upsertStage(
                $policy,
                $agent,
                (int) $request->stage,
                $request->input('occurred_at'),
                $request->input('notes')
            );

            return $this->returnAllData([
                'id' => $leg->id,
                'stage' => $leg->stage,
                'label' => ReeferTripStages::label((int) $leg->stage),
                'occurred_at' => $leg->occurred_at?->format('Y-m-d H:i:s'),
                'notes' => $leg->notes,
            ], __('alerts.success'));
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?? __('main.not_allowed');

            return $this->returnError(422, $msg);
        } catch (\Exception $e) {
            return $this->returnError(500, $e->getMessage());
        }
    }

    public function upload_reefer_trip_document(Request $request)
    {
        if ($response = $this->rejectIfPayloadTooLarge($request)) {
            return $response;
        }

        try {
            $request->validate([
                'delivery_policy_id' => 'required|exists:delivery_policies,id',
                'stage' => 'nullable|integer|min:1|max:11',
                'document' => 'required|file|mimes:png,jpg,jpeg,pdf|max:10240',
            ]);

            $agent = auth()->guard('agent')->user();
            $policy = DeliveryPolicy::with(['money_transfer', 'booking_containers.booking'])
                ->findOrFail($request->delivery_policy_id);

            $this->tracking->assertAgentCanAccess($policy, $agent);

            $imageableId = $policy->id;
            $imageableType = DeliveryPolicy::class;

            if ($request->filled('stage')) {
                $stage = (int) $request->stage;
                if (! ReeferTripStages::isValid($stage)) {
                    return $this->returnError(422, __('validation.in', ['attribute' => 'stage']));
                }
                $leg = ReeferTripLeg::firstOrCreate(
                    [
                        'delivery_policy_id' => $policy->id,
                        'stage' => $stage,
                    ],
                    [
                        'booking_container_id' => $policy->booking_containers()->first()?->id,
                        'recorded_by_agent_id' => $agent->id,
                    ]
                );
                $imageableId = $leg->id;
                $imageableType = ReeferTripLeg::class;
            }

            $stored = $this->storeMorphImageFromRequest(
                $request,
                'document',
                self::DOC_FOLDER,
                $imageableId,
                $imageableType,
                true
            );

            if ($stored instanceof \Illuminate\Http\JsonResponse) {
                return $stored;
            }

            return $this->returnAllData([
                'id' => $stored->id,
                'url' => $stored->image,
                'stage' => $request->input('stage'),
            ], __('alerts.success'));
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?? __('main.not_allowed');

            return $this->returnError(422, $msg);
        } catch (\Exception $e) {
            return $this->returnError(500, $e->getMessage());
        }
    }
}
