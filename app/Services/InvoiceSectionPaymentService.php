<?php

namespace App\Services;

use App\Models\Invoice;
use App\Support\CompanyCurrencyLedger;
use App\Support\InvoiceCurrency;

class InvoiceSectionPaymentService
{
    public const SECTIONS = ['tax', 'receipt', 'additional', 'usd'];

    public const SECTION_LABELS = [
        'tax' => 'ضريبية (I)',
        'receipt' => 'إيصالات (R)',
        'additional' => 'إضافية (S)',
        'usd' => 'بنود دولار',
    ];

    /**
     * Gross totals + paid + remaining per section (FIFO for legacy null section).
     *
     * @return array<string, array{total: float, paid: float, remaining: float, currency: string, label: string}>
     */
    public static function getSectionsBreakdown(Invoice $invoice): array
    {
        $sectionTotals = app(InvoicePrintBuilder::class)->getInvoiceSectionTotals($invoice);

        $paidBySection = [
            'tax' => 0.0,
            'receipt' => 0.0,
            'additional' => 0.0,
            'usd' => 0.0,
        ];
        $nullEgp = 0.0;
        $nullUsd = 0.0;

        $payments = $invoice->relationLoaded('invoicePayments')
            ? $invoice->invoicePayments
            : $invoice->invoicePayments()->orderBy('id')->get();

        if (! $invoice->relationLoaded('invoicePayments')) {
            $payments = $payments->sortBy('id')->values();
        } else {
            $payments = $payments->sortBy('id')->values();
        }

        foreach ($payments as $payment) {
            if (! CompanyCurrencyLedger::paymentCountsTowardBalance($payment)) {
                continue;
            }

            $val = round((float) $payment->value, 2);
            $section = $payment->section ?? null;

            if ($section && isset($paidBySection[$section])) {
                $paidBySection[$section] = round($paidBySection[$section] + $val, 2);
                continue;
            }

            $currency = InvoiceCurrency::normalize($payment->currency ?? InvoiceCurrency::EGP);
            if ($currency === InvoiceCurrency::USD) {
                $nullUsd = round($nullUsd + $val, 2);
            } else {
                $nullEgp = round($nullEgp + $val, 2);
            }
        }

        // Legacy null EGP: FIFO tax → receipt → additional
        foreach (['tax', 'receipt', 'additional'] as $sec) {
            if ($nullEgp <= 0) {
                break;
            }
            $currentNet = max(0.0, round($sectionTotals[$sec] - $paidBySection[$sec], 2));
            $deduct = min($nullEgp, $currentNet);
            $paidBySection[$sec] = round($paidBySection[$sec] + $deduct, 2);
            $nullEgp = round($nullEgp - $deduct, 2);
        }

        // Leftover null EGP then null USD → usd bucket
        if ($nullEgp > 0) {
            $nullUsd = round($nullUsd + $nullEgp, 2);
            $nullEgp = 0.0;
        }
        if ($nullUsd > 0) {
            $currentNet = max(0.0, round($sectionTotals['usd'] - $paidBySection['usd'], 2));
            $deduct = min($nullUsd, $currentNet);
            $paidBySection['usd'] = round($paidBySection['usd'] + $deduct, 2);
        }

        $invoiceIsUsd = InvoiceCurrency::isUsd($invoice->currency ?? null);
        $breakdown = [];
        foreach (self::SECTIONS as $sec) {
            $total = round((float) ($sectionTotals[$sec] ?? 0), 2);
            $paid = round((float) ($paidBySection[$sec] ?? 0), 2);
            $breakdown[$sec] = [
                'total' => $total,
                'paid' => $paid,
                'remaining' => max(0.0, round($total - $paid, 2)),
                // Pure USD invoices: I/R/S live in the USD bucket. Hybrid: only `usd` is $.
                'currency' => ($sec === 'usd' || $invoiceIsUsd)
                    ? InvoiceCurrency::USD
                    : InvoiceCurrency::EGP,
                'label' => self::SECTION_LABELS[$sec],
            ];
        }

        return $breakdown;
    }

    public static function sectionRemaining(Invoice $invoice, string $section): float
    {
        $breakdown = self::getSectionsBreakdown($invoice);

        return (float) ($breakdown[$section]['remaining'] ?? 0);
    }
}
