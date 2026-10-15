<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;

/**
 * مسارات التشغيل حسب نوع العملية (bookings.type_of_action).
 *
 * كل المسارات تستخدم خانات المراحل الثلاث نفسها: 0 = التخصيص، 1 = المرحلة الثانية، 2 = المرحلة الثالثة.
 * ما يتغير هو اسم الخانة، ووجود الانتظار، وآخر مرحلة في المسار.
 */
final class ShipmentWorkflow
{
    public const EXPORT = 0;
    public const INBOUND = 1;
    public const CLEARANCE = 2;
    public const STUFFING = 3;
    public const REEFER = 4;

    /** workflow => [stage slot => tab key] (slot 0 is always specification). */
    public const PATHS = [
        self::EXPORT => [1 => 'loading', 2 => 'unloading'],
        self::INBOUND => [1 => 'inbound_release', 2 => 'inbound_empty_return'],
        self::CLEARANCE => [1 => 'clearance'],
        self::STUFFING => [1 => 'stuffing'],
        self::REEFER => [1 => 'reefer_loading', 2 => 'reefer_unloading'],
    ];

    public const WITH_WAITING = [self::EXPORT, self::REEFER];

    public const NAMES = [
        self::EXPORT => 'تصدير',
        self::INBOUND => 'وارد',
        self::CLEARANCE => 'تخليص',
        self::STUFFING => 'تستيف',
        self::REEFER => 'صادر برادات',
    ];

    public const LABELS = [
        'specification' => 'التخصيص',
        'waiting' => 'الانتظار',
        'loading' => 'التحميل',
        'unloading' => 'التعتيق',
        'inbound_release' => 'الصرف',
        'inbound_empty_return' => 'تعتيق فارغ',
        'clearance' => 'التخليص',
        'stuffing' => 'التستيف',
        'reefer_loading' => 'تحميل برادات',
        'reefer_unloading' => 'تعتيق برادات',
    ];

    public static function effectiveFrom(): ?Carbon
    {
        $value = config('workflows.effective_from');

        return $value ? Carbon::parse($value) : null;
    }

    public static function of(?Booking $booking): int
    {
        $code = $booking ? trim((string) $booking->type_of_action) : '';
        if (! in_array($code, ['1', '2', '3', '4'], true)) {
            return self::EXPORT;
        }
        $cutoff = self::effectiveFrom();
        $createdAt = $booking->getRawOriginal('created_at');
        if ($cutoff && $createdAt && Carbon::parse($createdAt)->lt($cutoff)) {
            return self::EXPORT;
        }

        return (int) $code;
    }

    public static function hasStage(int $workflow, int $type): bool
    {
        return $type === 0 || isset(self::PATHS[$workflow][$type]);
    }

    public static function hasWaiting(int $workflow): bool
    {
        return in_array($workflow, self::WITH_WAITING, true);
    }

    public static function lastStage(int $workflow): int
    {
        return max(array_keys(self::PATHS[$workflow]));
    }

    public static function stageKey(int $workflow, int $type): string
    {
        return $type === 0 ? 'specification' : self::PATHS[$workflow][$type];
    }

    public static function label(int $workflow, int $type): string
    {
        return self::LABELS[self::stageKey($workflow, $type)];
    }

    /**
     * Tab key => [workflows (null = all), stage slot or 'waiting'].
     *
     * @return array<string, array{0: int[]|null, 1: int|string}>
     */
    public static function tabs(): array
    {
        $tabs = ['specification' => [null, 0], 'waiting' => [self::WITH_WAITING, 'waiting']];
        foreach (self::PATHS as $workflow => $stages) {
            foreach ($stages as $type => $key) {
                $tabs[$key] = [[$workflow], $type];
            }
        }

        return $tabs;
    }

    public static function tab(string $key): array
    {
        $tabs = self::tabs();
        abort_unless(isset($tabs[$key]), 422, 'تبويب غير معروف');

        return $tabs[$key];
    }

    /**
     * SQL condition (with bindings) selecting bookings that follow one of the workflows.
     *
     * @param  int[]  $workflows
     * @return array{0: string, 1: array}
     */
    public static function bookingSql(array $workflows, string $table = 'bookings'): array
    {
        $cutoff = self::effectiveFrom()?->format('Y-m-d H:i:s');
        $parts = [];
        $bindings = [];
        foreach ($workflows as $workflow) {
            $workflow = (int) $workflow;
            if ($workflow === self::EXPORT) {
                $sql = "($table.type_of_action IS NULL OR $table.type_of_action NOT IN ('1','2','3','4')";
                if ($cutoff) {
                    $sql .= " OR $table.created_at < ?";
                    $bindings[] = $cutoff;
                }
            } else {
                $sql = "($table.type_of_action = '$workflow'";
                if ($cutoff) {
                    $sql .= " AND $table.created_at >= ?";
                    $bindings[] = $cutoff;
                }
            }
            $parts[] = $sql.')';
        }

        return ['('.implode(' OR ', $parts).')', $bindings];
    }

    /** Restrict a bookings query to the workflows. */
    public static function constrainBookings($query, array $workflows)
    {
        [$sql, $bindings] = self::bookingSql($workflows, $query->getModel()->getTable());

        return $query->whereRaw($sql, $bindings);
    }

    /** Container-level condition of a stage slot (same conditions as the existing tabs). */
    public static function constrainSlot($query, int|string $slot)
    {
        return match ($slot) {
            0 => $query->where(fn ($q) => $q->where('status', 0)
                ->orWhere(fn ($q2) => $q2->where('status', 1)->where('superagent_specification_approved', 0))),
            'waiting' => $query->where('superagent_specification_approved', 1)->where('is_in_loading', 0)
                ->where('superagent_loading_approved', 0)->where('superagent_unloading_approved', 0),
            1 => $query->where('superagent_specification_approved', 1)->where('is_in_loading', 1)
                ->where('superagent_loading_approved', 0)->where('superagent_unloading_approved', 0),
            2 => $query->where('superagent_specification_approved', 1)->where('superagent_loading_approved', 1)
                ->where('superagent_unloading_approved', 0),
        };
    }

    /** Restrict a booking_containers query to a tab (slot condition + booking workflow). */
    public static function constrainContainers($query, string $tab)
    {
        [$workflows, $slot] = self::tab($tab);
        self::constrainSlot($query, $slot);
        if ($workflows !== null) {
            $query->whereHas('booking', fn ($booking) => self::constrainBookings($booking, $workflows));
        }

        return $query;
    }

    public static function describe(?Booking $booking): array
    {
        $workflow = self::of($booking);
        $stages = [['type_id' => 0, 'key' => 'specification', 'label' => self::LABELS['specification']]];
        foreach (self::PATHS[$workflow] as $type => $key) {
            $stages[] = ['type_id' => $type, 'key' => $key, 'label' => self::LABELS[$key]];
        }

        return [
            'type' => $workflow,
            'name' => self::NAMES[$workflow],
            'has_waiting' => self::hasWaiting($workflow),
            'stages' => $stages,
        ];
    }
}
