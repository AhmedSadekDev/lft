<?php

namespace App\Support;

/**
 * EGP / USD helpers for dashboard invoicing and company AR.
 *
 * Company AR keeps separate ledgers:
 * - EGP lines settle only in the EGP bucket
 * - USD lines settle only in the USD bucket (foreign_amount)
 *
 * exchange_rate is optional metadata / internal EGP book for agent cost.
 * When present, price = foreign_amount * rate for wallet/cost only — it must
 * not fold into the company's EGP receivable.
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

        return [
            'currency' => self::USD,
            'foreign_amount' => $entered,
            'exchange_rate' => $rate > 0 ? $rate : null,
            // Internal EGP book only when a rate is provided — not company EGP AR.
            'price' => $rate > 0 ? round($entered * $rate, 2) : 0.0,
        ];
    }

    /**
     * Amount to include in an invoice total in the invoice's primary currency.
     * USD lines never fold into an EGP invoice total (use foreignUsdAmount / usd_amount).
     */
    public static function invoiceLineAmount(object $line, ?string $invoiceCurrency): float
    {
        $invoiceCurrency = self::normalize($invoiceCurrency);
        $lineCurrency = self::normalize($line->currency ?? self::EGP);

        if ($invoiceCurrency === self::USD) {
            if ($lineCurrency === self::USD) {
                return round((float) ($line->foreign_amount ?? $line->price ?? $line->value ?? 0), 2);
            }

            // EGP line on a USD invoice: keep EGP book amount (rare).
            return round((float) ($line->price ?? $line->value ?? 0), 2);
        }

        // EGP invoice AR — native EGP lines only.
        if ($lineCurrency === self::USD) {
            return 0.0;
        }

        return round((float) ($line->price ?? $line->value ?? 0), 2);
    }

    /**
     * USD receivable amount for a line (0 when the line is not USD).
     */
    public static function foreignUsdAmount(object $line): float
    {
        if (! self::isUsd($line->currency ?? null)) {
            return 0.0;
        }

        return round((float) ($line->foreign_amount ?? 0), 2);
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

        // Hybrid EGP invoice: show USD as its own bucket (rate is optional metadata).
        if ($rate > 0 && $egp > 0) {
            return number_format($usd, 2) . ' $ (مرجع × '
                . rtrim(rtrim(number_format($rate, 6, '.', ''), '0'), '.')
                . ' ≈ ' . number_format($egp, 2) . ' ج.م)';
        }

        return number_format($usd, 2) . ' $';
    }
}
