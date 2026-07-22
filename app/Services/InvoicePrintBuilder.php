<?php

namespace App\Services;

use App\Mappers\InvoicePrintSectionMapper;
use App\Mappers\ServiceCategoryStatusMapper;
use App\Models\Booking;
use App\Models\BookingService;
use App\Models\Invoice;
use Illuminate\Support\Collection;

class InvoicePrintBuilder
{
    /**
     * Build printable invoice groups for a booking invoice.
     *
     * @return array{
     *   invoice_number: string,
     *   tax: array{section: string, suffix: string, label: string, number: string, items: Collection, subtotal: float, vat: float, discount: float, total: float},
     *   receipt: array{section: string, suffix: string, label: string, number: string, items: Collection, subtotal: float, vat: float, discount: float, total: float},
     *   additional: array{section: string, suffix: string, label: string, number: string, items: Collection, subtotal: float, vat: float, discount: float, total: float},
     *   combined_items: Collection,
     *   combined_total: float
     * }
     */
    public function build(Invoice $invoice): array
    {
        $booking = $invoice->booking;
        $booking->loadMissing([
            'bookingContainers.container',
            'bookingContainers.branch.factory',
            'bookingContainers.delivery_policies.money_transfer',
            'bookingServices.service.serviceCategory',
            'expenses.service.serviceCategory',
        ]);

        $baseNumber = (string) ($invoice->invoice_number ?? '');

        $taxItems = collect($booking->bookingContainers ?? []);
        $receiptItems = collect();
        $additionalItems = collect();

        foreach ($booking->bookingServices as $bookingService) {
            $section = $this->resolveServiceSection($bookingService);

            match ($section) {
                InvoicePrintSectionMapper::TAX => $taxItems->push($bookingService),
                InvoicePrintSectionMapper::RECEIPT => $receiptItems->push($bookingService),
                default => $additionalItems->push($bookingService),
            };
        }

        $taxItems = $this->sortPrintServices($taxItems);
        $receiptItems = $this->sortPrintServices($receiptItems);
        $additionalItems = $this->sortPrintServices($additionalItems);

        // Agent expenses from app (untaxed / additional) → additional block
        $agentExpenseRows = $booking->expenses()
            ->whereHas('service.serviceCategory', function ($q) {
                $q->where(function ($inner) {
                    $inner->where('invoice_print_section', InvoicePrintSectionMapper::ADDITIONAL)
                        ->orWhere(function ($fallback) {
                            $fallback->whereNull('invoice_print_section')
                                ->whereIn('service_status', [
                                    ServiceCategoryStatusMapper::UNTAXED,
                                    ServiceCategoryStatusMapper::NOT_INVOICED,
                                ]);
                        });
                });
            })
            ->orderBy('id')
            ->get()
            ->map(fn ($expense) => (object) [
                'type' => 'agent_expense_attachment',
                'expense' => $expense,
                'price' => (float) ($expense->value ?? 0),
            ]);

        $additionalItems = $additionalItems->concat($agentExpenseRows);

        // Group receipt booking services by receipt family for cleaner print
        $receiptItems = $this->groupReceiptServices($receiptItems);

        $taxSubtotal = $this->sumItems($taxItems);
        // Tax block uses invoice VAT/discount already calculated for tax base
        $vat = (float) ($invoice->value_added_tax_amount ?? 0);
        $discount = (float) ($invoice->discount_amount ?? 0);
        $taxTotal = (float) ($invoice->invoice_total_after_discount ?? max(0, $taxSubtotal + $vat - $discount));

        $receiptSubtotal = $this->sumItems($receiptItems);
        $additionalSubtotal = $this->sumItems($additionalItems);

        $taxGroup = $this->makeGroup(
            InvoicePrintSectionMapper::TAX,
            $baseNumber,
            $taxItems,
            $taxSubtotal,
            $vat,
            $discount,
            $taxTotal
        );

        $receiptGroup = $this->makeGroup(
            InvoicePrintSectionMapper::RECEIPT,
            $baseNumber,
            $receiptItems,
            $receiptSubtotal
        );

        $additionalGroup = $this->makeGroup(
            InvoicePrintSectionMapper::ADDITIONAL,
            $baseNumber,
            $additionalItems,
            $additionalSubtotal
        );

        $combinedItems = $taxItems
            ->concat($receiptItems)
            ->concat($additionalItems)
            ->values();

        return [
            'invoice_number' => $baseNumber,
            'tax' => $taxGroup,
            'receipt' => $receiptGroup,
            'additional' => $additionalGroup,
            'combined_items' => $combinedItems,
            'combined_total' => $taxTotal + $receiptSubtotal + $additionalSubtotal,
        ];
    }

    public function resolveServiceSection(BookingService $bookingService): string
    {
        $fullName = (string) ($bookingService->full_name ?? '');
        if ($this->looksLikeReceipt($fullName)) {
            return InvoicePrintSectionMapper::RECEIPT;
        }

        $category = $bookingService->service?->serviceCategory;
        if ($category && filled($category->invoice_print_section)
            && in_array($category->invoice_print_section, InvoicePrintSectionMapper::getValidValues(), true)
        ) {
            return $category->invoice_print_section;
        }

        return InvoicePrintSectionMapper::fromServiceStatus($category?->service_status ?? null);
    }

    private function looksLikeReceipt(string $fullName): bool
    {
        return stripos($fullName, 'ايصالات') !== false
            || stripos($fullName, 'إيصالات') !== false
            || stripos($fullName, 'receipt') !== false;
    }

    /**
     * Keep containers first; prefer "مصاريف أخرى" before "بيانه" among services.
     */
    private function sortPrintServices(Collection $items): Collection
    {
        $containers = $items->filter(fn ($item) => $item instanceof \App\Models\BookingContainer)->values();
        $rest = $items->reject(fn ($item) => $item instanceof \App\Models\BookingContainer)->values();

        $rest = $rest->sortBy(function ($item) {
            if (!($item instanceof BookingService)) {
                return 9;
            }
            $fullName = (string) ($item->full_name ?? '');
            if (stripos($fullName, 'مصاريف أخرى') !== false || stripos($fullName, 'مصاريف اخري') !== false) {
                return 1;
            }
            if (stripos($fullName, 'بيانه') !== false || stripos($fullName, 'بيان') !== false) {
                return 2;
            }

            return 3;
        })->values();

        return $containers->concat($rest)->values();
    }

    private function groupReceiptServices(Collection $receiptServices): Collection
    {
        $bookingServices = $receiptServices->filter(fn ($item) => $item instanceof BookingService);
        $others = $receiptServices->reject(fn ($item) => $item instanceof BookingService);

        $groups = $bookingServices->groupBy(function (BookingService $service) {
            $fullName = (string) ($service->full_name ?? '');
            $parts = preg_split('/(ايصالات|إيصالات)/iu', $fullName, 2, PREG_SPLIT_DELIM_CAPTURE);
            if (count($parts) >= 2) {
                $before = trim($parts[0] ?? '');
                $after = trim($parts[2] ?? '');
                $key = $before !== '' ? $before : ($after !== '' ? $after : 'عام');

                return $key;
            }

            return 'عام';
        });

        $grouped = collect();
        foreach ($groups as $groupKey => $services) {
            $grouped->push((object) [
                'type' => 'grouped_receipt',
                'group_key' => $groupKey,
                'services' => $services,
                'total_price' => $services->sum('price'),
                'price' => $services->sum('price'),
                'notes' => $services->pluck('note')->filter()->values()->all(),
                'count' => $services->count(),
            ]);
        }

        return $grouped->concat($others)->values();
    }

    private function sumItems(Collection $items): float
    {
        return (float) $items->sum(function ($item) {
            if ($item instanceof BookingService) {
                return (float) ($item->price ?? 0);
            }
            if (is_object($item) && isset($item->type) && $item->type === 'grouped_receipt') {
                return (float) ($item->total_price ?? 0);
            }
            if (is_object($item) && isset($item->type) && $item->type === 'agent_expense_attachment') {
                return (float) ($item->expense->value ?? $item->price ?? 0);
            }
            if (is_object($item) && isset($item->price)) {
                return (float) $item->price;
            }

            // BookingContainer
            return (float) ($item->price ?? 0);
        });
    }

    private function makeGroup(
        string $section,
        string $baseNumber,
        Collection $items,
        float $subtotal,
        float $vat = 0,
        float $discount = 0,
        ?float $total = null
    ): array {
        $suffix = InvoicePrintSectionMapper::suffix($section);

        return [
            'section' => $section,
            'suffix' => $suffix,
            'label' => InvoicePrintSectionMapper::label($section, 'ar'),
            'number' => $baseNumber !== '' ? $baseNumber . '-' . $suffix : $suffix,
            'items' => $items->values(),
            'subtotal' => $subtotal,
            'vat' => $vat,
            'discount' => $discount,
            'total' => $total ?? $subtotal,
        ];
    }
}
