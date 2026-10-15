<?php

namespace App\Support;

/**
 * EGP / USD helpers for dashboard invoicing.
 *
 * Rule of thumb:
 * - booking_services.price / agent_expenses.value always store the EGP book amount
 *   (wallet, bank, vault, EGP invoice totals).
 * - When currency is USD, foreign_amount + exchange_rate hold the original dollar
 *   line; price = foreign_amount * exchange_rate.
 * - invoices.currency = USD means a pure international invoice: print/totals use
 *   foreign USD amounts (and container prices entered in USD).
 */
class InvoiceCurrency
{
    public const EGP = 'EGP';
    public const USD = 'USD';

    public static function normalize(?string $currency): string
    {
        $currency = strtoupper(trim((string) $currency));

        return $currency === self::USD ? self::USD : self::EGP;
    }

    public static function isUsd(?string $currency): bool
    {
        return self::normalize($currency) === self::USD;
    }

    public static function label(?string $currency, string $locale = 'ar'): string
    {
        if (self::isUsd($currency)) {
            return $locale === 'en' ? 'USD' : 'دولار';
        }

        return $locale === 'en' ? 'EGP' : 'ج.م';
    }

    public static function symbol(?string $currency): string
    {
        return self::isUsd($currency) ? '$' : 'ج.م';
    }

    /**
     * Normalize a dashboard receipt line before save.
     *
     * @param  array{currency?: string, price?: float|string, foreign_amount?: float|string|null, exchange_rate?: float|string|null}  $input
     * @return array{currency: string, price: float, foreign_amount: ?float, exchange_rate: ?float}
     */
    public static function normalizeLine(array $input): array
    {
        $currency = self::normalize($input['currency'] ?? self::EGP);
        $entered = round((float) ($input['price'] ?? $input['foreign_amount'] ?? 0), 2);

        if ($currency === self::EGP) {
            return [
                'currency' => self::EGP,
                'price' => $entered,
                'foreign_amount' => null,
                'exchange_rate' => null,
            ];
        }

        $rate = round((float) ($input['exchange_rate'] ?? 0), 6);
        if ($rate <= 0) {
            throw new \InvalidArgumentException('سعر الصرف مطلوب وأكبر من صفر عند اختيار الدولار.');
        }

        return [
            'currency' => self::USD,
            'foreign_amount' => $entered,
            'exchange_rate' => $rate,
            'price' => round($entered * $rate, 2),
        ];
    }

    /**
     * Amount to show / total on an invoice in the invoice's currency.
     */
    public static function invoiceLineAmount(object $line, ?string $invoiceCurrency): float
    {
        $invoiceCurrency = self::normalize($invoiceCurrency);
        $lineCurrency = self::normalize($line->currency ?? self::EGP);

        if ($invoiceCurrency === self::USD) {
            if ($lineCurrency === self::USD) {
                return round((float) ($line->foreign_amount ?? $line->price ?? $line->value ?? 0), 2);
            }

            // EGP line on a USD invoice: keep EGP book amount (rare; prefer all-USD lines).
            return round((float) ($line->price ?? $line->value ?? 0), 2);
        }

        // EGP invoice — always the EGP book amount.
        return round((float) ($line->price ?? $line->value ?? 0), 2);
    }

    public static function formatLine(object $line, ?string $invoiceCurrency = self::EGP): string
    {
        $invoiceCurrency = self::normalize($invoiceCurrency);
        $lineCurrency = self::normalize($line->currency ?? self::EGP);
        $egp = round((float) ($line->price ?? $line->value ?? 0), 2);

        if ($lineCurrency !== self::USD) {
            return number_format($egp, 2) . ' ' . self::symbol($invoiceCurrency === self::USD ? self::USD : self::EGP);
        }

        $usd = round((float) ($line->foreign_amount ?? 0), 2);
        $rate = round((float) ($line->exchange_rate ?? 0), 6);

        if ($invoiceCurrency === self::USD) {
            return number_format($usd, 2) . ' $';
        }

        if ($rate > 0) {
            return number_format($usd, 2) . ' $ × ' . rtrim(rtrim(number_format($rate, 6, '.', ''), '0'), '.')
                . ' = ' . number_format($egp, 2) . ' ج.م';
        }

        return number_format($usd, 2) . ' $ (' . number_format($egp, 2) . ' ج.م)';
    }
}
