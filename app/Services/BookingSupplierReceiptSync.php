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
     * When a Receipt is linked to a booking (and not already mirrored),
     * ensure supplier balance is handled by ReceiptController — this method
     * only clears stale booking_service link if booking was removed.
     */
    public function syncFromReceipt(Receipt $receipt): void
    {
        if (!$receipt->booking_id && $receipt->booking_service_id) {
            $bookingService = BookingService::query()
                ->lockForUpdate()
                ->find($receipt->booking_service_id);

            if ($bookingService && $bookingService->payment_type === 'supplier') {
                // Unlink from booking service without deleting the service row
                $receipt->booking_service_id = null;
                $receipt->save();
            }
        }
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
