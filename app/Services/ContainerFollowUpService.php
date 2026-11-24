<?php

namespace App\Services;

use App\Models\BookingContainer;
use App\Models\ContainerFollowUp;
use App\Models\DeliveryPolicy;
use App\Models\ReeferTrip;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContainerFollowUpService
{
    public function record(array $data, ?int $userId = null): ContainerFollowUp
    {
        $status = trim((string) ($data['status_text'] ?? ''));
        if ($status === '') {
            throw ValidationException::withMessages(['status_text' => 'الحالة مطلوبة']);
        }

        $occurredAt = ! empty($data['occurred_at'])
            ? Carbon::parse($data['occurred_at'])
            : now();

        return DB::transaction(function () use ($data, $status, $occurredAt, $userId) {
            $followUp = ContainerFollowUp::create([
                'booking_container_id' => $data['booking_container_id'] ?? null,
                'delivery_policy_id' => $data['delivery_policy_id'] ?? null,
                'reefer_trip_id' => $data['reefer_trip_id'] ?? null,
                'status_text' => $status,
                'occurred_at' => $occurredAt,
                'recorded_at' => now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            if (! empty($data['reefer_trip_id'])) {
                ReeferTrip::whereKey($data['reefer_trip_id'])->update([
                    'current_status' => $status,
                ]);
            }

            return $followUp;
        });
    }

    public function timelineForContainer(BookingContainer $container): array
    {
        return $this->followUpRowsForContainer($container)
            ->map(fn (ContainerFollowUp $row) => $this->normalizeFollowUp($row))
            ->values()
            ->all();
    }

    public function timelineForPolicy(DeliveryPolicy $policy): array
    {
        $rows = ContainerFollowUp::query()
            ->where('delivery_policy_id', $policy->id)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty() && method_exists($policy, 'reeferTripLegs')) {
            $policy->loadMissing('reeferTripLegs');
            return app(ReeferTripTrackingService::class)->buildTimeline($policy);
        }

        return $rows->map(fn (ContainerFollowUp $row) => $this->normalizeFollowUp($row))->all();
    }

    /**
     * Shape expected by leader-main MovementTimeline:
     * date, time, status, description, place_of_activity (newest first).
     */
    public function publicMovementsForContainer(BookingContainer $container): array
    {
        $rows = $this->followUpRowsForContainer($container);

        if ($rows->isNotEmpty()) {
            return $rows
                ->sortByDesc(fn (ContainerFollowUp $row) => [
                    optional($row->occurred_at)->timestamp ?? 0,
                    $row->id,
                ])
                ->values()
                ->map(fn (ContainerFollowUp $row) => $this->toPublicMovement($row))
                ->all();
        }

        // Fallback: reefer legs linked via delivery policies.
        $container->loadMissing('delivery_policies.reeferTripLegs');
        $policy = $container->delivery_policies->first();
        if ($policy) {
            $timeline = app(ReeferTripTrackingService::class)->buildTimeline($policy);
            $movements = [];
            foreach (array_reverse($timeline) as $leg) {
                $occurred = $leg['occurred_at'] ?? null;
                if (! $occurred && empty($leg['label']) && empty($leg['notes'])) {
                    continue;
                }
                $parsed = $occurred ? Carbon::parse($occurred) : null;
                $movements[] = [
                    'date' => $parsed?->format('Y-m-d'),
                    'time' => $parsed?->format('H:i'),
                    'status' => $leg['label'] ?? ($leg['stage'] ?? null),
                    'description' => $leg['notes'] ?? ($leg['label'] ?? null),
                    'place_of_activity' => null,
                ];
            }
            if ($movements !== []) {
                return $movements;
            }
        }

        return [];
    }

    private function followUpRowsForContainer(BookingContainer $container)
    {
        if ($container->relationLoaded('delivery_policies')) {
            $policyIds = $container->delivery_policies->pluck('id')->filter()->values()->all();
        } else {
            $policyIds = $container->delivery_policies()
                ->pluck('delivery_policies.id')
                ->filter()
                ->values()
                ->all();
        }

        return ContainerFollowUp::query()
            ->where(function ($q) use ($container, $policyIds) {
                $q->where('booking_container_id', $container->id);
                if ($policyIds !== []) {
                    $q->orWhereIn('delivery_policy_id', $policyIds);
                }
            })
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();
    }

    private function normalizeFollowUp(ContainerFollowUp $row): array
    {
        return [
            'id' => $row->id,
            'status' => $row->status_text,
            'occurred_at' => optional($row->occurred_at)->format('Y-m-d H:i'),
            'recorded_at' => optional($row->recorded_at)->format('Y-m-d H:i'),
            'notes' => $row->notes,
        ];
    }

    private function toPublicMovement(ContainerFollowUp $row): array
    {
        $occurred = $row->occurred_at;

        return [
            'date' => optional($occurred)->format('Y-m-d'),
            'time' => optional($occurred)->format('H:i'),
            'status' => $row->status_text,
            'description' => $row->notes ?: $row->status_text,
            'place_of_activity' => null,
        ];
    }
}
