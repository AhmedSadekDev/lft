<?php

namespace App\Mappers;

class InvoicePrintSectionMapper extends BaseMapper
{
    public const TAX        = 'tax';
    public const RECEIPT    = 'receipt';
    public const ADDITIONAL = 'additional';
    /** Not printed anywhere — completely hidden from all invoice outputs. */
    public const HIDDEN     = 'hidden';

    public static function getAll(string $locale = 'en'): array
    {
        return match ($locale) {
            'ar' => [
                self::TAX        => 'فاتورة ضريبية',
                self::RECEIPT    => 'إيصالات',
                self::ADDITIONAL => 'خدمات إضافية',
                self::HIDDEN     => 'لا تظهر في الفاتورة',
            ],
            default => [
                self::TAX        => 'Tax Invoice',
                self::RECEIPT    => 'Receipts',
                self::ADDITIONAL => 'Additional Services',
                self::HIDDEN     => 'Hidden (not printed)',
            ],
        };
    }

    public static function getValidValues(): array
    {
        return [
            self::TAX,
            self::RECEIPT,
            self::ADDITIONAL,
            self::HIDDEN,
        ];
    }

    public static function suffix(string $section): string
    {
        return match ($section) {
            self::TAX        => 'I',
            self::RECEIPT    => 'R',
            self::ADDITIONAL => 'S',
            default          => '',
        };
    }

    public static function label(string $section, string $locale = 'ar'): string
    {
        return self::getAll($locale)[$section] ?? $section;
    }

    /**
     * Default section from legacy service_status when invoice_print_section is empty.
     */
    public static function fromServiceStatus($serviceStatus): string
    {
        return match ((int) $serviceStatus) {
            ServiceCategoryStatusMapper::TAXED         => self::TAX,
            ServiceCategoryStatusMapper::UNTAXED,
            ServiceCategoryStatusMapper::NOT_INVOICED  => self::ADDITIONAL,
            default                                    => self::ADDITIONAL,
        };
    }
}
