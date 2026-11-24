<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Car;
use App\Models\Driver;
use App\Models\ReeferShipment;
use App\Models\ReeferTrip;
use App\Services\ContainerFollowUpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReeferTripController extends Controller
{
    public function __construct(private ContainerFollowUpService $followUps)
    {
        $this->middleware('permission:reefer_trips.index')->only(['index', 'show']);
        $this->middleware('permission:reefer_trips.manage')->except(['index', 'show']);
    }

    public function index(Request $request)
    {
        try {
            $query = ReeferTrip::query()
                ->with(['shipment', 'driver:id,name', 'car:id,car_number', 'agent:id,name'])
                ->latest('id');

            if ($request->filled('q')) {
                $q = trim((string) $request->q);
                $query->where(function ($inner) use ($q) {
                    $inner->where('foreign_trailer', 'like', "%{$q}%")
                        ->orWhere('bol_number', 'like', "%{$q}%")
                        ->orWhere('driver_name', 'like', "%{$q}%")
                        ->orWhere('car_number', 'like', "%{$q}%")
                        ->orWhere('certificate_no', 'like', "%{$q}%")
                        ->orWhereHas('shipment', fn ($s) => $s->where('permit_number', 'like', "%{$q}%"));
                });
            }
            if ($request->filled('country')) {
                $query->where('destination_country', 'like', '%'.$request->country.'%');
            }

            return view('admin.reefer-trips.index', [
                'trips' => $query->paginate(30)->appends($request->query()),
                'shipments' => ReeferShipment::query()->latest('id')->limit(50)->get(),
                'q' => $request->get('q'),
                'country' => $request->get('country'),
            ]);
        } catch (\Throwable $e) {
            return view('admin.reefer-trips.index', [
                'trips' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 30),
                'shipments' => collect(),
                'q' => $request->get('q'),
                'country' => $request->get('country'),
            ])->with('error', 'جداول البرادات غير جاهزة — طبّق docs/sql/2026-11-22-usd-followup-reefer-module.sql');
        }
    }

    public function create()
    {
        return view('admin.reefer-trips.form', [
            'trip' => new ReeferTrip(),
            'shipments' => ReeferShipment::query()->latest('id')->get(),
            'drivers' => Driver::query()->orderBy('name')->get(['id', 'name']),
            'cars' => Car::query()->orderBy('car_number')->get(['id', 'car_number']),
            'agents' => Agent::query()->orderBy('name')->get(['id', 'name', 'wallet', 'wallet_usd']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        try {
            DB::transaction(function () use ($data, $request) {
                if ($request->filled('new_permit_number')) {
                    $shipment = ReeferShipment::create([
                        'permit_number' => $request->input('new_permit_number'),
                        'destination_country' => $data['destination_country'] ?? null,
                        'factory_name' => $data['factory_name'] ?? null,
                        'planned_count' => (int) $request->input('planned_count', 1),
                        'created_by' => auth()->id(),
                    ]);
                    $data['reefer_shipment_id'] = $shipment->id;
                }

                $data['created_by'] = auth()->id();
                $data['current_status'] = $data['current_status'] ?? 'تم التسجيل';
                $trip = ReeferTrip::create($data);

                $this->applyDualCustody($trip, null, (float) $trip->custody_egp, (float) $trip->custody_usd);

                $this->followUps->record([
                    'reefer_trip_id' => $trip->id,
                    'status_text' => $trip->current_status,
                    'occurred_at' => $trip->trip_date?->format('Y-m-d H:i:s') ?? now()->toDateTimeString(),
                    'notes' => 'إنشاء رحلة براد',
                ], auth()->id());
            });
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('reefer_trips.index')->with('success', __('alerts.added_successfully'));
    }

    public function show(ReeferTrip $reeferTrip)
    {
        $reeferTrip->load(['shipment', 'driver', 'car', 'agent', 'followUps.creator']);

        return view('admin.reefer-trips.show', [
            'trip' => $reeferTrip,
            'timelineFields' => ReeferTrip::timelineFields(),
        ]);
    }

    public function edit(ReeferTrip $reeferTrip)
    {
        return view('admin.reefer-trips.form', [
            'trip' => $reeferTrip,
            'shipments' => ReeferShipment::query()->latest('id')->get(),
            'drivers' => Driver::query()->orderBy('name')->get(['id', 'name']),
            'cars' => Car::query()->orderBy('car_number')->get(['id', 'car_number']),
            'agents' => Agent::query()->orderBy('name')->get(['id', 'name', 'wallet', 'wallet_usd']),
        ]);
    }

    public function update(Request $request, ReeferTrip $reeferTrip)
    {
        $data = $this->validated($request);
        $oldEgp = (float) $reeferTrip->custody_egp;
        $oldUsd = (float) $reeferTrip->custody_usd;

        try {
            DB::transaction(function () use ($reeferTrip, $data, $oldEgp, $oldUsd) {
                $reeferTrip->update($data);
                $this->applyDualCustody($reeferTrip->fresh(), $reeferTrip->agent_id, (float) $reeferTrip->custody_egp - $oldEgp, (float) $reeferTrip->custody_usd - $oldUsd);
            });
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('reefer_trips.show', $reeferTrip)->with('success', __('alerts.updated_successfully'));
    }

    public function updateTimeline(Request $request, ReeferTrip $reeferTrip)
    {
        $fields = array_keys(ReeferTrip::timelineFields());
        $rules = ['current_status' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:2000', 'departure_port' => 'nullable|string|max:191'];
        foreach ($fields as $field) {
            $rules[$field] = 'nullable|date';
        }
        $data = $request->validate($rules);

        $reeferTrip->fill($data);
        if (! empty($data['current_status'])) {
            $reeferTrip->current_status = $data['current_status'];
        }
        $reeferTrip->save();

        if ($request->filled('current_status') || $request->boolean('log_follow_up')) {
            $this->followUps->record([
                'reefer_trip_id' => $reeferTrip->id,
                'status_text' => $request->input('current_status', $reeferTrip->current_status ?: 'تحديث مسار'),
                'occurred_at' => $request->input('occurred_at'),
                'notes' => $request->input('notes'),
            ], auth()->id());
        }

        return back()->with('success', __('alerts.updated_successfully'));
    }

    public function destroy(ReeferTrip $reeferTrip)
    {
        $reeferTrip->delete();

        return redirect()->route('reefer_trips.index')->with('success', __('alerts.deleted_successfully'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'reefer_shipment_id' => 'nullable|integer|exists:reefer_shipments,id',
            'trip_date' => 'nullable|date',
            'loading_site' => 'nullable|string|max:191',
            'destination_country' => 'nullable|string|max:191',
            'factory_name' => 'nullable|string|max:191',
            'driver_name' => 'nullable|string|max:191',
            'driver_passport' => 'nullable|string|max:191',
            'driver_phone' => 'nullable|string|max:64',
            'driver_id' => 'nullable|exists:drivers,id',
            'car_number' => 'nullable|string|max:191',
            'trailer_number' => 'nullable|string|max:191',
            'car_id' => 'nullable|exists:cars,id',
            'foreign_trailer' => 'nullable|string|max:191',
            'data_logger_1' => 'nullable|string|max:191',
            'data_logger_2' => 'nullable|string|max:191',
            'certificate_no' => 'nullable|string|max:191',
            'bol_number' => 'nullable|string|max:191',
            'custody_egp' => 'nullable|numeric|min:0',
            'custody_usd' => 'nullable|numeric|min:0',
            'agent_id' => 'nullable|exists:agents,id',
            'notes' => 'nullable|string|max:5000',
            'current_status' => 'nullable|string|max:255',
        ]);
    }

    private function applyDualCustody(ReeferTrip $trip, ?int $fallbackAgentId, float $egpDiff, float $usdDiff): void
    {
        $agentId = $trip->agent_id ?: $fallbackAgentId;
        if (! $agentId) {
            return;
        }

        $agent = Agent::query()->lockForUpdate()->find($agentId);
        if (! $agent) {
            return;
        }

        if ($egpDiff != 0.0) {
            if ($egpDiff > 0 && (float) $agent->wallet < $egpDiff) {
                throw ValidationException::withMessages(['custody_egp' => __('main.you dont have enougth money')]);
            }
            $agent->wallet = round((float) $agent->wallet - $egpDiff, 2);
        }

        if ($usdDiff != 0.0) {
            if ($usdDiff > 0 && (float) ($agent->wallet_usd ?? 0) < $usdDiff) {
                throw ValidationException::withMessages(['custody_usd' => 'رصيد دولار المندوب غير كافٍ']);
            }
            $agent->wallet_usd = round((float) ($agent->wallet_usd ?? 0) - $usdDiff, 2);
        }

        $agent->save();
    }
}
