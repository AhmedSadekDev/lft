<?php

namespace App\Support;

/**
 * Fixed 11-stage reefer land trip timeline (صادر برادات only).
 * Stages 1–10 use occurred_at; stage 11 is notes-only.
 */
final class ReeferTripStages
{
    public const NOTES = 11;

    public const LABELS = [
        1 => 'دخول الميناء المصري',
        2 => 'خروج من الميناء المصري',
        3 => 'وصول سفاجا',
        4 => 'حجز عبارة',
        5 => 'وصول ضباء',
        6 => 'خروج من ضباء',
        7 => 'وصول جمرك دولة الوصول',
        8 => 'خروج من السلع',
        9 => 'وصول المخزن',
        10 => 'تم التعتيق',
        11 => 'ملاحظات',
    ];

    public static function all(): array
    {
        return array_keys(self::LABELS);
    }

    public static function isValid(int $stage): bool
    {
        return isset(self::LABELS[$stage]);
    }

    public static function isNotesOnly(int $stage): bool
    {
        return $stage === self::NOTES;
    }

    public static function label(int $stage, ?string $locale = null): string
    {
        $key = 'main.reefer_trip_stage_'.$stage;
        $translated = __($key);
        if ($translated !== $key) {
            return $translated;
        }

        return self::LABELS[$stage] ?? (string) $stage;
    }
}
