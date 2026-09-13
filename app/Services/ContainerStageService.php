<?php

namespace App\Services;

use App\Models\BookingContainer;
use App\Models\BookingContainerAgent;
use App\Models\BookingContainerStage;
use App\Models\DailyBookingContainer;
use Illuminate\Support\Facades\DB;

class ContainerStageService
{
    public const NAMES = [0 => 'specification', 1 => 'loading', 2 => 'unloading'];

    public function currentType(BookingContainer $container): int
    {
        return ! $container->superagent_specification_approved ? 0 : (! $container->superagent_loading_approved ? 1 : 2);
    }

    public function assignments(int $containerId, int $type)
    {
        return BookingContainerAgent::where('booking_container_id', $containerId)->where('stage_type', $type);
    }

    public function assertAssigned(BookingContainer $container, int $type, int $agentId): void
    {
        abort_unless($this->assignments($container->id, $type)->where('agent_id', $agentId)->lockForUpdate()->first(), 403, 'المندوب غير مكلف بهذه المرحلة');
    }

    public function assertAvailable(BookingContainer $container, int $type): void
    {
        abort_unless(isset(self::NAMES[$type]), 422, 'مرحلة غير صحيحة');
        if ($type === 1) {
            abort_unless($container->superagent_specification_approved && ($container->is_in_loading || $container->superagent_loading_approved), 409, 'التحميل لم يبدأ بعد');
        } elseif ($type === 2) {
            abort_unless($container->superagent_loading_approved, 409, 'يجب اعتماد التحميل أولاً');
        }
    }

    // Caller must hold the container lock for every stage mutation, including receipts.
    public function receipts(BookingContainer $container, int $type): BookingContainerStage
    {
        abort_unless(isset(self::NAMES[$type]), 422, 'مرحلة غير صحيحة');

        // A locking read is essential under MySQL REPEATABLE READ: an earlier expense
        // snapshot must not hide a closure committed while we waited for the container.
        return BookingContainerStage::lockForUpdate()->firstOrCreate(['booking_container_id' => $container->id, 'type_id' => $type]);
    }

    public function assertReceiptsOpen(BookingContainer $container, int $type, int $agentId): BookingContainerStage
    {
        $this->assertAssigned($container, $type, $agentId);
        $this->assertAvailable($container, $type);
        $stage = $this->receipts($container, $type);
        abort_if($stage->receipts_closed_at, 409, 'تم إقفال إيصالات المرحلة');

        return $stage;
    }

    public function assign(int $containerId, array $agentIds, ?int $type = null): int
    {
        return DB::transaction(function () use ($containerId, $agentIds, $type) {
            $container = BookingContainer::lockForUpdate()->findOrFail($containerId);
            $type = $type ?? $this->currentType($container);
            abort_unless(isset(self::NAMES[$type]), 422, 'مرحلة غير صحيحة');
            abort_unless(\App\Models\Agent::whereIn('id', $agentIds)->count() === count(array_unique($agentIds)), 422, 'مندوب غير موجود');
            if ($type === 1) {
                abort_unless($container->superagent_specification_approved, 409, 'يجب اعتماد التخصيص أولاً');
            }
            if ($type === 2) {
                $this->assertAvailable($container, $type);
            }
            // Replace only this phase's assignees; other phases retain their access.
            $this->assignments($containerId, $type)->whereNotIn('agent_id', $agentIds)->delete();
            foreach (array_unique($agentIds) as $agentId) {
                $this->assignments($containerId, $type)->firstOrCreate(['agent_id' => $agentId], [
                    'booking_container_id' => $containerId,
                    'stage_type' => $type,
                    'booking_container_status' => $type,
                    'superagent_specification_approved' => (int) $container->superagent_specification_approved,
                    'superagent_loading_approved' => (int) $container->superagent_loading_approved,
                    'superagent_unloading_approved' => (int) $container->superagent_unloading_approved,
                    'is_in_loading' => (int) $container->is_in_loading,
                ]);
            }

            return $type;
        });
    }

    public function complete(int $containerId, int $type, ?int $agentId = null): bool
    {
        return DB::transaction(function () use ($containerId, $type, $agentId) {
            $container = BookingContainer::lockForUpdate()->findOrFail($containerId);
            $this->assertAvailable($container, $type);
            if ($agentId !== null) {
                $this->assertAssigned($container, $type, $agentId);
            }
            $field = self::NAMES[$type].'_completed_at';
            if ($container->$field || $container->{'superagent_'.self::NAMES[$type].'_approved'} || (int) $container->status >= $type + 1) {
                return false;
            }
            $now = now();
            $container->update(['status' => max((int) $container->status, $type + 1), $field => $now]);
            $this->assignments($containerId, $type)->update([$field => $now]);
            DailyBookingContainer::where('booking_container_id', $containerId)->update(['booking_container_status' => $container->status]);

            return true;
        });
    }

    public function approve(int $containerId, int $type): bool
    {
        return DB::transaction(function () use ($containerId, $type) {
            $container = BookingContainer::lockForUpdate()->findOrFail($containerId);
            $this->assertAvailable($container, $type);
            $name = self::NAMES[$type];
            $flag = 'superagent_'.$name.'_approved';
            if ($container->$flag) {
                return false;
            }
            $values = [$flag => 1, $name.'_approved_at' => now(), $name.'_completed_at' => $container->{$name.'_completed_at'} ?: now()];
            $container->update($values + ['status' => max((int) $container->status, $type + 1)]);
            $this->assignments($containerId, $type)->update($values);
            DailyBookingContainer::where('booking_container_id', $containerId)->update([$flag => 1, 'booking_container_status' => $container->status]);
            // Keep the existing specification -> loading handoff; unloading is assigned explicitly.
            if ($type === 0 && ! $this->assignments($containerId, 1)->exists()) {
                $this->assign($containerId, $this->assignments($containerId, 0)->pluck('agent_id')->all(), 1);
            }

            return true;
        });
    }

    public function closeReceipts(int $containerId, int $type, int $superagentId, int $version): BookingContainerStage
    {
        return DB::transaction(function () use ($containerId, $type, $superagentId, $version) {
            $container = BookingContainer::lockForUpdate()->findOrFail($containerId);
            $stage = $this->receipts($container, $type);
            if ($stage->receipts_closed_at) {
                return $stage;
            }
            abort_unless($container->{'superagent_'.self::NAMES[$type].'_approved'}, 409, 'يجب اعتماد انتهاء المرحلة أولاً');
            abort_unless($stage->version === $version, 409, 'تغيرت إيصالات المرحلة؛ راجعها مجدداً قبل الإقفال');
            $stage->update(['receipts_closed_at' => now(), 'receipts_closed_by' => $superagentId, 'version' => $stage->version + 1]);

            return $stage;
        });
    }

    public function moveToLoading(array $containerIds, bool $toLoading): void
    {
        DB::transaction(function () use ($containerIds, $toLoading) {
            $containers = BookingContainer::whereIn('id', $containerIds)->orderBy('id')->lockForUpdate()->get();
            foreach ($containers as $container) {
                abort_unless($container->superagent_specification_approved, 409, 'يجب اعتماد التخصيص أولاً');
                abort_if($container->status >= 2 || $container->superagent_loading_approved, 409, 'لا يمكن تغيير قائمة الانتظار بعد انتهاء التحميل');
                $values = ['is_in_loading' => (int) $toLoading, 'moved_to_loading_at' => $toLoading ? ($container->moved_to_loading_at ?: now()) : null];
                $container->update($values);
                $this->assignments($container->id, 1)->update($values);
                DailyBookingContainer::where('booking_container_id', $container->id)->update($values);
            }
        });
    }

    public function rewind(int $containerId, int $target): void
    {
        DB::transaction(function () use ($containerId, $target) {
            $container = BookingContainer::lockForUpdate()->findOrFail($containerId);
            abort_unless(in_array($target, [0, 1, 2], true) && $target < (int) $container->status, 422, 'حالة الرجوع غير صحيحة');
            abort_if((int) $container->status > $target + 1, 409, 'لا يمكن تجاوز مراحل منفذة عند الرجوع');
            abort_if(BookingContainerAgent::where('booking_container_id', $containerId)->where('stage_type', '>', $target)->exists(), 409, 'بدأ تكليف المرحلة التالية؛ لا يمكن الرجوع');
            abort_if(\App\Models\AgentExpense::where('booking_container_id', $containerId)->where('type_id', '>', $target)->exists(), 409, 'توجد مصروفات للمرحلة التالية');
            abort_if($container->stages()->where('type_id', '>=', $target)->whereNotNull('receipts_closed_at')->exists(), 409, 'إيصالات المرحلة مقفولة');
            $values = ['status' => $target];
            foreach (self::NAMES as $type => $name) {
                if ($type >= $target) {
                    $values['superagent_'.$name.'_approved'] = 0;
                    $values[$name.'_completed_at'] = null;
                    $values[$name.'_approved_at'] = null;
                }
            }
            $container->update($values);
            unset($values['status']);
            BookingContainerAgent::where('booking_container_id', $containerId)->where('stage_type', '>=', $target)->update($values);
            DailyBookingContainer::where('booking_container_id', $containerId)->update(['booking_container_status' => $target]);
        });
    }

    public function visibleContainers(int $agentId, int $type)
    {
        $query = BookingContainer::whereHas('agents', function ($q) use ($agentId, $type) {
            $q->where('agents.id', $agentId)->where('booking_container_agents.stage_type', $type);
        })->whereDoesntHave('stages', function ($q) use ($type) {
            $q->where('type_id', $type)->whereNotNull('receipts_closed_at');
        });
        if ($type === 1) {
            $query->where('superagent_specification_approved', 1)->where(function ($q) {
                $q->where('is_in_loading', 1)->orWhere('superagent_loading_approved', 1);
            });
        } elseif ($type === 2) {
            $query->where('superagent_loading_approved', 1);
        }

        return $query;
    }
}
