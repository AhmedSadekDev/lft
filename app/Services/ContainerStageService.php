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
        return $this->forAssignmentStage(BookingContainerAgent::where('booking_container_id', $containerId), $type);
    }

    private function forAssignmentStage($query, int $type)
    {
        // Legacy rows use the assignment's own approval snapshot, just like the migration.
        return $query->where(function ($q) use ($type) {
            $q->where('booking_container_agents.stage_type', $type)
                ->orWhere(function ($legacy) use ($type) {
                    $legacy->whereNull('booking_container_agents.stage_type')->whereRaw(
                        'CASE WHEN COALESCE(booking_container_agents.superagent_specification_approved, 0) = 0 THEN 0 WHEN COALESCE(booking_container_agents.superagent_loading_approved, 0) = 0 THEN 1 ELSE 2 END = ?',
                        [$type]
                    );
                });
        });
    }

    public function assertAssigned(BookingContainer $container, int $type, int $agentId): void
    {
        abort_if($container->booking()->whereHas('invoice')->exists(), 409, 'تم إصدار فاتورة لهذا الطلب، ولم يعد متاحاً للمندوب.');
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
            abort_if($container->booking()->whereHas('invoice')->exists(), 409, 'تم إصدار فاتورة لهذا الطلب، ولم يعد متاحاً للإسناد.');
            abort_if($container->{'superagent_'.self::NAMES[$type].'_approved'}, 409, 'المرحلة معتمدة بالفعل؛ اختر المرحلة الحالية للإسناد أو أرسل الطلب بدون type_id.');
            abort_if($container->stages()->where('type_id', $type)->whereNotNull('receipts_closed_at')->exists(), 409, 'إيصالات المرحلة مقفولة؛ لا يمكن إسنادها مجدداً.');
            if ($type === 1) {
                abort_unless($container->superagent_specification_approved, 409, 'يجب اعتماد التخصيص أولاً');
            }
            if ($type === 2) {
                $this->assertAvailable($container, $type);
            }
            // Replace only this phase's assignees; other phases retain their access.
            $this->assignments($containerId, $type)->whereNotIn('agent_id', $agentIds)->delete();
            foreach (array_unique($agentIds) as $agentId) {
                $row = [
                    'booking_container_id' => $containerId,
                    'agent_id' => $agentId,
                    'stage_type' => $type,
                    'booking_container_status' => $type,
                    'superagent_specification_approved' => (int) $container->superagent_specification_approved,
                    'superagent_loading_approved' => (int) $container->superagent_loading_approved,
                    'superagent_unloading_approved' => (int) $container->superagent_unloading_approved,
                    'is_in_loading' => (int) $container->is_in_loading,
                ];
                $assignment = $this->assignments($containerId, $type)->where('agent_id', $agentId)->first()
                    ?? new BookingContainerAgent;
                $assignment->fill($row)->save();
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

    public function approve(int $containerId, int $type, ?int $superagentId = null): bool
    {
        return DB::transaction(function () use ($containerId, $type, $superagentId) {
            $container = BookingContainer::lockForUpdate()->findOrFail($containerId);
            abort_if($container->booking()->whereHas('invoice')->exists(), 409, 'تم إصدار فاتورة لهذا الطلب، ولم يعد متاحاً.');
            $this->assertAvailable($container, $type);
            $name = self::NAMES[$type];
            $flag = 'superagent_'.$name.'_approved';
            if ($container->$flag) {
                return false;
            }
            $now = now();
            $values = [$flag => 1, $name.'_approved_at' => $now, $name.'_completed_at' => $container->{$name.'_completed_at'} ?: $now];
            // بعد اعتماد التخصيص انقل الحاوية مباشرة للتحميل بدون انتظار 24 ساعة / قائمة الانتظار.
            if ($type === 0) {
                $values['is_in_loading'] = 1;
                $values['moved_to_loading_at'] = $container->moved_to_loading_at ?: $now;
            }
            $container->update($values + ['status' => max((int) $container->status, $type + 1)]);
            $this->assignments($containerId, $type)->update($values + ['stage_type' => $type]);
            $dailyUpdate = [$flag => 1, 'booking_container_status' => $container->status];
            if ($type === 0) {
                $dailyUpdate['is_in_loading'] = 1;
            }
            DailyBookingContainer::where('booking_container_id', $containerId)->update($dailyUpdate);

            // انسخ مندوبي التخصيص لمرحلة التحميل إن لم يكونوا مكلفين بها بعد.
            if ($type === 0) {
                $agentIds = $this->assignments($containerId, 0)->pluck('agent_id')->all();
                if ($agentIds) {
                    $this->assign($containerId, $agentIds, 1);
                }
            }

            return true;
        });
    }

    /**
     * Close the reviewed receipts independently of operational stage approval.
     */
    public function closeReceipts(int $containerId, int $type, int $superagentId, int $version): BookingContainerStage
    {
        return DB::transaction(function () use ($containerId, $type, $superagentId, $version) {
            $container = BookingContainer::lockForUpdate()->findOrFail($containerId);
            $stage = $this->receipts($container, $type);
            if ($stage->receipts_closed_at) {
                return $stage; // already closed — idempotent
            }
            $this->assertAvailable($container, $type);
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

    public function returnToPreviousStage(int $containerId, int $expectedStatus): void
    {
        DB::transaction(function () use ($containerId, $expectedStatus) {
            $container = BookingContainer::lockForUpdate()->findOrFail($containerId);
            abort_unless((int) $container->status === $expectedStatus, 409, __('container_stages.status_changed'));
            abort_unless($expectedStatus >= 1 && $expectedStatus <= 3, 422, __('container_stages.already_specification'));
            abort_if($container->booking()->whereHas('invoice')->exists(), 409, __('container_stages.invoiced'));

            $target = $expectedStatus - 1;
            // Dashboard rollback cancels later assignments, including automatic ones.
            // Later assignments are deleted; expenses are retained and closed receipts are reopened.
            foreach (array_keys(self::NAMES) as $type) {
                if ($type > $target) {
                    $this->assignments($containerId, $type)->delete();
                }
            }
            $this->rewind($containerId, $target);
        });
    }

    public function rewind(int $containerId, int $target): void
    {
        DB::transaction(function () use ($containerId, $target) {
            $container = BookingContainer::lockForUpdate()->findOrFail($containerId);
            abort_if($container->booking()->whereHas('invoice')->exists(), 409, __('container_stages.invoiced'));
            abort_unless(in_array($target, [0, 1, 2], true) && $target < (int) $container->status, 422, __('container_stages.invalid_return'));
            abort_if((int) $container->status > $target + 1, 409, __('container_stages.cannot_skip'));
            foreach (array_keys(self::NAMES) as $type) {
                abort_if($type > $target && $this->assignments($containerId, $type)->exists(), 409, __('container_stages.next_assigned'));
            }

            $container->stages()->where('type_id', '>=', $target)->whereNotNull('receipts_closed_at')->update([
                'receipts_closed_at' => null,
                'receipts_closed_by' => null,
                'version' => DB::raw('version + 1'),
            ]);
            $values = ['status' => $target];
            if ($target === 0) {
                $values['is_in_loading'] = 0;
                $values['moved_to_loading_at'] = null;
            }
            if ($target === 1) {
                $values['is_in_loading'] = 1;
                $values['moved_to_loading_at'] = $container->moved_to_loading_at ?: now();
            }
            foreach (self::NAMES as $type => $name) {
                if ($type >= $target) {
                    $values['superagent_'.$name.'_approved'] = 0;
                    $values[$name.'_completed_at'] = null;
                    $values[$name.'_approved_at'] = null;
                }
            }
            $container->update($values);
            unset($values['status']);
            $this->assignments($containerId, $target)->update($values + ['booking_container_status' => $target, 'stage_type' => $target]);
            $dailyValues = ['booking_container_status' => $target];
            foreach (self::NAMES as $type => $name) {
                if ($type >= $target) {
                    $dailyValues['superagent_'.$name.'_approved'] = 0;
                }
            }
            if ($target <= 1) {
                $dailyValues += ['is_in_loading' => $target, 'moved_to_loading_at' => $container->moved_to_loading_at];
            }
            DailyBookingContainer::where('booking_container_id', $containerId)->update($dailyValues);
        });
    }

    public function visibleContainers(int $agentId, int $type)
    {
        abort_unless(isset(self::NAMES[$type]), 422, 'مرحلة غير صحيحة');
        $flag = 'superagent_'.self::NAMES[$type].'_approved';
        // بعد اعتماد التشغيل تختفي الحاوية فورًا من قائمة المندوب للمرحلة الحالية.
        $query = BookingContainer::whereDoesntHave('booking.invoice')->whereHas('agents', function ($q) use ($agentId, $type) {
            $this->forAssignmentStage($q->where('agents.id', $agentId), $type);
        })->where(fn ($q) => $q->where($flag, 0)->orWhereNull($flag))->whereDoesntHave('stages', function ($q) use ($type) {
            $q->where('type_id', $type)->whereNotNull('receipts_closed_at');
        });
        if ($type === 1) {
            $query->where('superagent_specification_approved', 1)->where('is_in_loading', 1);
        } elseif ($type === 2) {
            $query->where('superagent_loading_approved', 1);
        }

        return $query;
    }
}
