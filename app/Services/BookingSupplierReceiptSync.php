<?php

namespace App\Services;

use App\Models\BookingService;
use App\Models\Receipt;
use App\Models\Supplier;

class BookingSupplierReceiptSync
{
    /**
     * Keep a single Receipt row in sync with a BookingService paid via supplier.
     * No vault/bank/agent deduction — only supplier balance.
     */
    public function syncFromBookingService(BookingService $bookingService): ?Receipt
    {
        $bookingService->loadMissing(['service', 'booking']);

        if ($bookingService->payment_type !== 'supplier' || !$bookingService->supplier_id) {
            $this->detachAndDeleteLinkedReceipt($bookingService);

            return null;
        }

        $cost = round((float) $bookingService->price, 2);
        $invoiceNumber = filled($bookingService->supplier_invoice_number)
            ? $bookingService->supplier_invoice_number
            : ($bookingService->booking?->booking_number
                ?: ('BS-' . $bookingService->id));

        $payload = [
            'booking_id' => $bookingService->booking_id,
            'booking_service_id' => $bookingService->id,
            'cost' => $cost,
            'payment_source' => Receipt::PAYMENT_SOURCE_SUPPLIER,
            'supplier_id' => (int) $bookingService->supplier_id,
            'supplier_invoice_number' => $invoiceNumber,
            'notes' => $bookingService->note,
        ];

        $receipt = Receipt::query()
            ->where('booking_service_id', $bookingService->id)
            ->lockForUpdate()
            ->first();

        if ($receipt) {
            $this->reverseSupplierEffect($receipt);
            $receipt->update($payload);
            $receipt->refresh();
            $this->applySupplierEffect($receipt);

            return $receipt;
        }

        $receipt = Receipt::create($payload);
        $this->applySupplierEffect($receipt);

        return $receipt;
    }

    /**
     * Create/update BookingService from a supplier receipt so it appears
     * on the booking screen and in invoice receipt printing.
     */
    public function syncBookingServiceFromReceipt(Receipt $receipt, array $extra = []): ?BookingService
    {
        if (!$receipt->booking_id) {
            if ($receipt->booking_service_id) {
                BookingService::query()
                    ->where('id', $receipt->booking_service_id)
                    ->where('payment_type', 'supplier')
                    ->delete();
                $receipt->booking_service_id = null;
                $receipt->save();
            }

            return null;
        }

        $serviceId = $extra['service_id'] ?? null;
        if (!$serviceId && $receipt->booking_service_id) {
            $serviceId = BookingService::query()
                ->where('id', $receipt->booking_service_id)
                ->value('service_id');
        }

        if (!$serviceId) {
            return null;
        }

        $paymentType = match ($receipt->payment_source) {
            Receipt::PAYMENT_SOURCE_SUPPLIER => 'supplier',
            Receipt::PAYMENT_SOURCE_SAFE => 'vault',
            Receipt::PAYMENT_SOURCE_REPRESENTATIVE => 'agent',
            default => 'supplier',
        };

        $payload = [
            'booking_id' => $receipt->booking_id,
            'service_id' => (int) $serviceId,
            'price' => round((float) $receipt->cost, 2),
            'note' => $receipt->notes,
            'payment_type' => $paymentType,
            'supplier_id' => $receipt->payment_source === Receipt::PAYMENT_SOURCE_SUPPLIER
                ? $receipt->supplier_id
                : null,
            'supplier_invoice_number' => $receipt->supplier_invoice_number,
            'updated_by' => auth()->id(),
        ];

        if (!empty($extra['image'])) {
            $payload['image'] = $extra['image'];
        }

        if ($receipt->booking_service_id) {
            $bookingService = BookingService::query()
                ->lockForUpdate()
                ->find($receipt->booking_service_id);

            if ($bookingService) {
                $bookingService->update($payload);

                return $bookingService->fresh();
            }
        }

        $payload['created_by'] = auth()->id();
        $bookingService = BookingService::create($payload);

        $receipt->booking_service_id = $bookingService->id;
        $receipt->save();

        return $bookingService;
    }

    public function deleteLinkedReceipt(BookingService $bookingService): void
    {
        $this->detachAndDeleteLinkedReceipt($bookingService);
    }

    private function detachAndDeleteLinkedReceipt(BookingService $bookingService): void
    {
        $receipt = Receipt::query()
            ->where('booking_service_id', $bookingService->id)
            ->lockForUpdate()
            ->first();

        if (!$receipt) {
            return;
        }

        $this->reverseSupplierEffect($receipt);
        $receipt->delete();
    }

    private function applySupplierEffect(Receipt $receipt): void
    {
        if ($receipt->payment_source !== Receipt::PAYMENT_SOURCE_SUPPLIER || !$receipt->supplier_id) {
            return;
        }

        $this->adjustSupplierBalance((int) $receipt->supplier_id, (float) $receipt->cost);
    }

    private function reverseSupplierEffect(Receipt $receipt): void
    {
        if ($receipt->payment_source !== Receipt::PAYMENT_SOURCE_SUPPLIER || !$receipt->supplier_id) {
            return;
        }

        $this->adjustSupplierBalance((int) $receipt->supplier_id, -((float) $receipt->cost));
    }

    private function adjustSupplierBalance(int $supplierId, float $delta): void
    {
        $supplier = Supplier::query()->lockForUpdate()->findOrFail($supplierId);
        $supplier->balance = round((float) $supplier->balance + $delta, 2);
        $supplier->save();
    }
}
