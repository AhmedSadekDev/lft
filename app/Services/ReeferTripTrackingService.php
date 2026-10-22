<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\DeliveryPolicy;
use App\Models\ReeferTripLeg;
use App\Support\ReeferTripStages;
use App\Services\ShipmentWorkflow;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReeferTripTrackingService
{
    /**
     * Ownership used by existing delivery-policy APIs:
     * money_transfers.type = 3 AND transferer = agent.
     * Plus booking must be reefer (type_of_action = 4 / ShipmentWorkflow::REEFER).
     */
    public function agentOwnsReeferPolicy(DeliveryPolicy $policy, Agent $agent): bool
    {
        $policy->loadMissing(['money_transfer', 'booking_containers.booking']);

        if (($policy->money_transfer?->transferer_id) !== $agent->id) {
            return false;
        }

        if (($policy->money_transfer?->transferer_type) !== Agent::class
            && ($policy->money_transfer?->transferer_type) !== 'App\\Models\\Agent') {
            return false;
        }

        return $this->policyIsReefer($policy);
    }

    public function policyIsReefer(DeliveryPolicy $policy): bool
    {
        $policy->loadMissing('booking_containers.booking');

        foreach ($policy->booking_containers as $container) {
            $booking = $container->booking;
            if ($booking && ShipmentWorkflow::of($booking) === ShipmentWorkflow::REEFER) {
                return true;
            }
        }

        return false;
    }

    public function assertAgentCanAccess(DeliveryPolicy $policy, Agent $agent): void
    {
        if (! $this->agentOwnsReeferPolicy($policy, $agent)) {
            throw ValidationException::withMessages([
                'delivery_policy_id' => [__('main.not_allowed')],
            ]);
        }
    }

    public function ownedReeferPoliciesQuery(Agent $agent): Builder
    {
        return DeliveryPolicy::query()
            ->whereHas('money_transfer', function ($q) use ($agent) {
                $q->where('transferer_id', $agent->id)
                    ->where(function ($inner) {
                        $inner->where('transferer_type', Agent::class)
                            ->orWhere('transferer_type', 'App\\Models\\Agent');
                    });
            })
            ->whereHas('booking_containers.booking', function ($q) {
                $q->where('type_of_action', (string) ShipmentWorkflow::REEFER);
            })
            ->with([
                'driver',
                'car',
                'money_transfer',
                'booking_containers.loading',
                'booking_containers.booking',
            ])
            ->orderByDesc('id');
    }

    public function paginateForAgent(Agent $agent, int $perPage = 20, int $page = 1): LengthAwarePaginator
    {
        return $this->ownedReeferPoliciesQuery($agent)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function upsertStage(
        DeliveryPolicy $policy,
        Agent $agent,
        int $stage,
        ?string $occurredAt,
        ?string $notes
    ): ReeferTripLeg {
        $this->assertAgentCanAccess($policy, $agent);

        if (! ReeferTripStages::isValid($stage)) {
            throw ValidationException::withMessages([
                'stage' => [__('validation.in', ['attribute' => 'stage'])],
            ]);
        }

        if (ReeferTripStages::isNotesOnly($stage)) {
            $occurredAt = null;
        } elseif (empty($occurredAt)) {
            throw ValidationException::withMessages([
                'occurred_at' => [__('validation.required', ['attribute' => 'occurred_at'])],
            ]);
        }

        $containerId = $policy->booking_containers()->first()?->id;

        return DB::transaction(function () use ($policy, $agent, $stage, $occurredAt, $notes, $containerId) {
            return ReeferTripLeg::updateOrCreate(
                [
                    'delivery_policy_id' => $policy->id,
                    'stage' => $stage,
                ],
                [
                    'booking_container_id' => $containerId,
                    'occurred_at' => $occurredAt,
                    'notes' => $notes,
                    'recorded_by_agent_id' => $agent->id,
                ]
            );
        });
    }

    /**
     * @return list<array{stage:int,label:string,occurred_at:?string,notes:?string,documents:array}>
     */
    public function buildTimeline(DeliveryPolicy $policy): array
    {
        $policy->loadMissing(['reeferTripLegs.images']);
        $byStage = $policy->reeferTripLegs->keyBy('stage');

        $timeline = [];
        foreach (ReeferTripStages::all() as $stage) {
            /** @var ReeferTripLeg|null $leg */
            $leg = $byStage->get($stage);
            $timeline[] = [
                'stage' => $stage,
                'label' => ReeferTripStages::label($stage),
                'notes_only' => ReeferTripStages::isNotesOnly($stage),
                'occurred_at' => $leg?->occurred_at?->format('Y-m-d H:i:s'),
                'notes' => $leg?->notes,
                'documents' => $leg
                    ? $leg->images->map(fn ($img) => [
                        'id' => $img->id,
                        'url' => $img->image,
                    ])->values()->all()
                    : [],
            ];
        }

        return $timeline;
    }
}
