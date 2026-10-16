<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\InvoicePayment;

/**
 * Dual EGP/USD buckets for company accounts receivable.
 *
 * @phpstan-type MoneyPair array{EGP: float, USD: float}
 */
final class CompanyCurrencyLedger
{
    /**
     * @return MoneyPair
     */
    public static function zero(): array
    {
        return [InvoiceCurrency::EGP => 0.0, InvoiceCurrency::USD => 0.0];
    }

    /**
     * @param  MoneyPair  $a
     * @param  MoneyPair  $b
     * @return MoneyPair
     */
    public static function add(array $a, array $b): array
    {
        return [
            InvoiceCurrency::EGP => round(($a[InvoiceCurrency::EGP] ?? 0) + ($b[InvoiceCurrency::EGP] ?? 0), 2),
            InvoiceCurrency::USD => round(($a[InvoiceCurrency::USD] ?? 0) + ($b[InvoiceCurrency::USD] ?? 0), 2),
        ];
    }

    /**
     * @param  MoneyPair  $a
     * @param  MoneyPair  $b
     * @return MoneyPair
     */
    public static function sub(array $a, array $b): array
    {
        return [
            InvoiceCurrency::EGP => round(($a[InvoiceCurrency::EGP] ?? 0) - ($b[InvoiceCurrency::EGP] ?? 0), 2),
            InvoiceCurrency::USD => round(($a[InvoiceCurrency::USD] ?? 0) - ($b[InvoiceCurrency::USD] ?? 0), 2),
        ];
    }

    /**
     * Gross invoice amounts by currency (before payments).
     *
     * @return MoneyPair
     */
    public static function invoiceTotals(Invoice $invoice, callable $egpTotalCalculator): array
    {
        $buckets = self::zero();
        $primary = (float) $egpTotalCalculator($invoice);

        if (InvoiceCurrency::isUsd($invoice->currency ?? null)) {
            $buckets[InvoiceCurrency::USD] = $primary;
        } else {
            $buckets[InvoiceCurrency::EGP] = $primary;
            $buckets[InvoiceCurrency::USD] = round((float) ($invoice->usd_amount ?? 0), 2);
        }

        return $buckets;
    }

    /**
     * Settled payments on an invoice, split by currency.
     *
     * @return MoneyPair
     */
    public static function invoicePaid(Invoice $invoice): array
    {
        $buckets = self::zero();

        $payments = $invoice->relationLoaded('invoicePayments')
            ? $invoice->invoicePayments
            : $invoice->invoicePayments()->get();

        foreach ($payments as $payment) {
            if (! self::paymentCountsTowardBalance($payment)) {
                continue;
            }
            $currency = InvoiceCurrency::normalize($payment->currency ?? InvoiceCurrency::EGP);
            $buckets[$currency] = round($buckets[$currency] + (float) $payment->value, 2);
        }

        return $buckets;
    }

    /**
     * @return MoneyPair
     */
    public static function invoiceRemaining(Invoice $invoice, callable $egpTotalCalculator): array
    {
        return self::sub(self::invoiceTotals($invoice, $egpTotalCalculator), self::invoicePaid($invoice));
    }

    public static function paymentCountsTowardBalance(InvoicePayment $payment): bool
    {
        if (($payment->payment_type ?? '') === 'check' && empty($payment->check_paid_at)) {
            return false;
        }

        return true;
    }

    /**
     * @return MoneyPair
     */
    public static function paymentBuckets($payments): array
    {
        $buckets = self::zero();
        foreach ($payments as $payment) {
            if (! self::paymentCountsTowardBalance($payment)) {
                continue;
            }
            $currency = InvoiceCurrency::normalize($payment->currency ?? InvoiceCurrency::EGP);
            $buckets[$currency] = round($buckets[$currency] + (float) $payment->value, 2);
        }

        return $buckets;
    }
}
