<?php

namespace App\Http\Controllers\Api\Superagent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Superagent\DeliveryPolicyResource;
use App\Models\DeliveryPolicy;
use Illuminate\Http\Request;

class DeliveryPolicyController extends Controller
{
    /**
     * List delivery policies created by agents (visible to manager).
     * Optional filters: aging_id (ميناء التعتيق), is_settled, search.
     */
    public function fetch_delivery_policies(Request $request)
    {
        try {
            $perPage = max(1, min((int) $request->get('per_page', 20), 100));
            $page = max(1, (int) $request->get('page', 1));

            $query = DeliveryPolicy::query()
                ->with([
                    'car',
                    'driver',
                    'money_transfer.transferer',
                    'image',
                    'booking_containers.booking',
                    'booking_containers.departure',
                    'booking_containers.loading',
                    'booking_containers.aging',
                    'booking_containers.branch',
                ])
                ->whereHas('booking_containers.booking', function ($q) {
                    $q->whereDoesntHave('invoice');
                })
                ->when($request->filled('aging_id'), function ($q) use ($request) {
                    $q->whereHas('booking_containers', function ($c) use ($request) {
                        $c->where('aging_id', (int) $request->aging_id);
                    });
                })
                ->when($request->has('is_settled'), function ($q) use ($request) {
                    $q->where('is_settled', (int) $request->boolean('is_settled'));
                })
                ->when($request->filled('search'), function ($q) use ($request) {
                    $term = '%'.$request->search.'%';
                    $q->where(function ($inner) use ($term) {
                        $inner->whereHas('driver', fn ($d) => $d->where('name', 'like', $term))
                            ->orWhereHas('car', fn ($c) => $c->where('car_number', 'like', $term))
                            ->orWhereHas('booking_containers', function ($c) use ($term) {
                                $c->where('container_no', 'like', $term)
                                    ->orWhereHas('booking', fn ($b) => $b->where('booking_number', 'like', $term));
                            });
                    });
                })
                ->orderByDesc('id');

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'errNum' => '0000',
                'message' => __('alerts.success'),
                'data' => DeliveryPolicyResource::collection($paginator->items()),
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

    public function delivery_policy_details(Request $request)
    {
        try {
            $request->validate([
                'delivery_policy_id' => 'required_without:booking_container_id|nullable|exists:delivery_policies,id',
                'booking_container_id' => 'required_without:delivery_policy_id|nullable|exists:booking_containers,id',
            ]);

            $policyQuery = DeliveryPolicy::with([
                'car',
                'driver',
                'money_transfer.transferer',
                'image',
                'booking_containers.booking',
                'booking_containers.departure',
                'booking_containers.loading',
                'booking_containers.aging',
                'booking_containers.branch',
                'reeferTripLegs.images',
            ]);

            if ($request->filled('delivery_policy_id')) {
                $policy = $policyQuery->findOrFail($request->delivery_policy_id);
            } else {
                $policy = $policyQuery
                    ->whereHas('booking_containers', fn ($q) => $q->where('booking_containers.id', (int) $request->booking_container_id))
                    ->orderByDesc('id')
                    ->first();
                if (! $policy) {
                    return $this->returnError(404, 'لا توجد بوليصة تحميل لهذه الحاوية');
                }
            }

            return $this->returnAllData(new DeliveryPolicyResource($policy), __('alerts.success'));
        } catch (\Exception $e) {
            return $this->returnError($e instanceof \Illuminate\Validation\ValidationException ? 422 : 500, $e->getMessage());
        }
    }
}
