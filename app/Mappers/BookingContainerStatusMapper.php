<?php

namespace App\Mappers;

class BookingContainerStatusMapper extends BaseMapper
{
    const SPECIFICATION = 0;
    const LOADING = 1;
    const UNLOADING = 2;
    const FINISHED = 3;

    static public function getAll(string $locale = 'en'): array
    {
        switch ($locale) {
            case 'ar':
                return [
                    self::SPECIFICATION => 'تخصيص',
                    self::LOADING => 'تحميل',
                    self::UNLOADING => 'تعتيق',
                    self::FINISHED => 'إنتهى',
                ];
                break;
            default: // English is the default
                return [
                    self::SPECIFICATION => 'Specification',
                    self::LOADING => 'Loading',
                    self::UNLOADING => 'Unloading',
                    self::FINISHED => 'Finished',
                ];
                break;
        }
    }

    static public function getValidValues(): array
    {
        return [
            self::SPECIFICATION,
            self::LOADING,
            self::UNLOADING,
            self::FINISHED,
        ];
    }
}
