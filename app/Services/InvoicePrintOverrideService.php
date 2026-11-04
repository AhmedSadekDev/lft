<?php

namespace App\Services;

use App\Models\AgentExpense;
use App\Models\BookingService;
use App\Models\Invoice;
use Illuminate\Support\Facades\Schema;

class InvoicePrintOverrideService
{
    public const KEY_AGENT_EXPENSES = 'agent_expenses';
    public const KEY_BOOKING_SERVICES = 'booking_services';

    public function supportsOverrides(): bool
    {
        static $supported = null;
        if ($supported === null) {
            try {
                $supported = Schema::hasColumn('invoices', 'print_overrides');
            } catch (\Throwable) {
                $supported = false;
            }
        }

        return $supported;
    }

    public function all(Invoice $invoice): array
    {
        if (! $this->supportsOverrides()) {
            return [self::KEY_AGENT_EXPENSES => [], self::KEY_BOOKING_SERVICES => []];
        }

        $raw = $invoice->print_overrides;
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (! is_array($raw)) {
            $raw = [];
        }

        return [
            self::KEY_AGENT_EXPENSES => (array) ($raw[self::KEY_AGENT_EXPENSES] ?? []),
            self::KEY_BOOKING_SERVICES => (array) ($raw[self::KEY_BOOKING_SERVICES] ?? []),
        ];
    }

    public function expenseOverride(Invoice $invoice, int $expenseId): array
    {
        $all = $this->all($invoice)[self::KEY_AGENT_EXPENSES];

        return (array) ($all[(string) $expenseId] ?? $all[$expenseId] ?? []);
    }

    public function serviceOverride(Invoice $invoice, int $serviceId): array
    {
        $all = $this->all($invoice)[self::KEY_BOOKING_SERVICES];

        return (array) ($all[(string) $serviceId] ?? $all[$serviceId] ?? []);
    }

    public function isExpenseHidden(Invoice $invoice, int $expenseId): bool
    {
        return (bool) ($this->expenseOverride($invoice, $expenseId)['hidden'] ?? false);
    }

    public function isServiceHidden(Invoice $invoice, int $serviceId): bool
    {
        return (bool) ($this->serviceOverride($invoice, $serviceId)['hidden'] ?? false);
    }

    /**
     * Effective invoice line amount for an agent expense (override value or original).
     */
    public function effectiveExpenseAmount(Invoice $invoice, AgentExpense $expense): float
    {
        $override = $this->expenseOverride($invoice, (int) $expense->id);
        if (array_key_exists('value', $override) && $override['value'] !== null && $override['value'] !== '') {
            return round((float) $override['value'], 2);
        }

        return round((float) ($expense->value ?? 0), 2);
    }

    /**
     * Effective invoice line amount for a booking service.
     */
    public function effectiveServiceAmount(Invoice $invoice, BookingService $service): float
    {
        $override = $this->serviceOverride($invoice, (int) $service->id);
        if (array_key_exists('value', $override) && $override['value'] !== null && $override['value'] !== '') {
            return round((float) $override['value'], 2);
        }

        return round((float) ($service->price ?? 0), 2);
    }

    public function setExpenseOverride(Invoice $invoice, int $expenseId, ?float $value = null, ?bool $hidden = null): Invoice
    {
        return $this->mergeOverride($invoice, self::KEY_AGENT_EXPENSES, $expenseId, $value, $hidden);
    }

    public function setServiceOverride(Invoice $invoice, int $serviceId, ?float $value = null, ?bool $hidden = null): Invoice
    {
        return $this->mergeOverride($invoice, self::KEY_BOOKING_SERVICES, $serviceId, $value, $hidden);
    }

    public function hideExpenseFromInvoice(Invoice $invoice, int $expenseId): Invoice
    {
        return $this->setExpenseOverride($invoice, $expenseId, null, true);
    }

    public function hideServiceFromInvoice(Invoice $invoice, int $serviceId): Invoice
    {
        return $this->setServiceOverride($invoice, $serviceId, null, true);
    }

    private function mergeOverride(
        Invoice $invoice,
        string $bucket,
        int $id,
        ?float $value,
        ?bool $hidden
    ): Invoice {
        if (! $this->supportsOverrides()) {
            abort(422, 'عمود print_overrides غير متوفر — نفّذ SQL المراجعة أولاً');
        }

        $all = $this->all($invoice);
        $key = (string) $id;
        $current = (array) ($all[$bucket][$key] ?? []);

        if ($value !== null) {
            abort_unless($value > 0, 422, 'قيمة الفاتورة يجب أن تكون أكبر من صفر');
            $current['value'] = round($value, 2);
        }
        if ($hidden !== null) {
            $current['hidden'] = $hidden;
        }

        $all[$bucket][$key] = $current;
        $invoice->print_overrides = $all;
        $invoice->save();

        return $invoice->refresh();
    }
}
