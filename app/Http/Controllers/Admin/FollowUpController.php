<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingContainer;
use App\Models\ContainerFollowUp;
use App\Models\DeliveryPolicy;
use App\Models\ReeferTrip;
use App\Services\ContainerFollowUpService;
use App\Services\ShipmentWorkflow;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FollowUpController extends Controller
{
    public function __construct(private ContainerFollowUpService $followUps)
    {
        $this->middleware('permission:follow_ups.index')->only(['index', 'show']);
        $this->middleware('permission:follow_ups.manage')->only(['store', 'update']);
    }

    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');

        $containersQuery = BookingContainer::query()
            ->with([
                'booking:id,booking_number,public_code,type_of_action,company_id',
                'booking.company:id,name',
                'delivery_policies.driver:id,name',
                'delivery_policies.car:id,car_number',
            ])
            ->with(['followUps' => fn ($q) => $q->latest('occurred_at')->limit(1)])
            ->whereHas('booking')
            ->latest('id');

        if ($filter === 'loading') {
            $containersQuery->whereHas('booking', fn ($q) => $q->whereIn('type_of_action', [
                (string) ShipmentWorkflow::EXPORT,
                (string) ShipmentWorkflow::STUFFING,
            ]));
        } elseif ($filter === 'aging') {
            $containersQuery->whereHas('booking', fn ($q) => $q->whereIn('type_of_action', [
                (string) ShipmentWorkflow::INBOUND,
                (string) ShipmentWorkflow::CLEARANCE,
            ]));
        } elseif ($filter === 'international' || $filter === 'reefer') {
            $containersQuery->whereHas('booking', fn ($q) => $q->where('type_of_action', (string) ShipmentWorkflow::REEFER));
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $containersQuery->where(function ($query) use ($q) {
                $query->where('container_no', 'like', "%{$q}%")
                    ->orWhereHas('booking', fn ($b) => $b->where('booking_number', 'like', "%{$q}%")
                        ->orWhere('public_code', 'like', "%{$q}%"));
            });
        }

        $reeferTrips = collect();
        if (in_array($filter, ['all', 'reefer', 'international'], true)) {
            try {
                $reeferTrips = ReeferTrip::query()
                    ->with(['driver:id,name', 'car:id,car_number', 'shipment'])
                    ->when($request->filled('q'), function ($query) use ($request) {
                        $q = trim((string) $request->q);
                        $query->where(function ($inner) use ($q) {
                            $inner->where('foreign_trailer', 'like', "%{$q}%")
                                ->orWhere('bol_number', 'like', "%{$q}%")
                                ->orWhere('driver_name', 'like', "%{$q}%")
                                ->orWhere('car_number', 'like', "%{$q}%");
                        });
                    })
                    ->latest('id')
                    ->limit(50)
                    ->get();
            } catch (\Throwable $e) {
                $reeferTrips = collect();
            }
        }

        return view('admin.follow-ups.index', [
            'containers' => $containersQuery->paginate(30)->appends($request->query()),
            'reeferTrips' => $reeferTrips,
            'filter' => $filter,
            'q' => $request->get('q'),
        ]);
    }

    public function show(Request $request)
    {
        $containerId = $request->integer('booking_container_id') ?: null;
        $policyId = $request->integer('delivery_policy_id') ?: null;
        $reeferTripId = $request->integer('reefer_trip_id') ?: null;

        $container = $containerId ? BookingContainer::with(['booking.company', 'delivery_policies.driver', 'delivery_policies.car'])->find($containerId) : null;
        $policy = $policyId ? DeliveryPolicy::with(['driver', 'car', 'booking_containers'])->find($policyId) : null;
        $reeferTrip = $reeferTripId ? ReeferTrip::with(['shipment', 'driver', 'car'])->find($reeferTripId) : null;

        $history = ContainerFollowUp::query()
            ->with('creator:id,name')
            ->where(function ($q) use ($containerId, $policyId, $reeferTripId) {
                if ($containerId) {
                    $q->orWhere('booking_container_id', $containerId);
                }
                if ($policyId) {
                    $q->orWhere('delivery_policy_id', $policyId);
                }
                if ($reeferTripId) {
                    $q->orWhere('reefer_trip_id', $reeferTripId);
                }
                if (! $containerId && ! $policyId && ! $reeferTripId) {
                    $q->whereRaw('1=0');
                }
            })
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get();

        return view('admin.follow-ups.show', compact('container', 'policy', 'reeferTrip', 'history'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'booking_container_id' => 'nullable|integer',
            'delivery_policy_id' => 'nullable|integer',
            'reefer_trip_id' => 'nullable|integer',
            'status_text' => 'required|string|max:255',
            'occurred_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->followUps->record($data, auth()->id());
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', __('alerts.updated_successfully'));
    }

    public function update(Request $request, ContainerFollowUp $followUp)
    {
        $data = $request->validate([
            'status_text' => 'required|string|max:255',
            'occurred_at' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $followUp->update($data);

        return back()->with('success', __('alerts.updated_successfully'));
    }
}
