<?php

namespace App\Mappers;

class ServiceCategoryStatusMapper extends BaseMapper
{
    const TAXED = 0;
    const UNTAXED = 1;
    const NOT_INVOICED = 2;

    static public function getAll(string $locale = 'en'): array
    {
        switch ($locale) {
            case 'ar':
                return [
                    self::TAXED => 'ضريبية',
                    self::UNTAXED => 'غير ضريبية',
                    self::NOT_INVOICED => 'غير مفوترة',
                ];
                break;
            default: // English is the default
                return [
                    self::TAXED => 'Taxed',
                    self::UNTAXED => 'Un-Taxed',
                    self::NOT_INVOICED => 'Not-Invoiced',
                ];
                break;
        }
    }

    static public function getValidValues(): array
    {
        return [
            self::TAXED,
            self::UNTAXED,
            self::NOT_INVOICED,
        ];
    }
}
