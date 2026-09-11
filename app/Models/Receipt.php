<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Receipt extends Model
{
    use HasFactory;

    public const PAYMENT_SOURCE_SAFE = 'safe';
    public const PAYMENT_SOURCE_REPRESENTATIVE = 'representative';
    public const PAYMENT_SOURCE_SUPPLIER = 'supplier';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'cost' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingService(): BelongsTo
    {
        return $this->belongsTo(BookingService::class);
    }

    public function scopeForSupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeFromSupplier($query)
    {
        return $query->where('payment_source', self::PAYMENT_SOURCE_SUPPLIER);
    }

    /**
     * Group receipts by supplier invoice number and sum costs.
     * Multiple receipts (across bookings/orders) with the same invoice appear as one row.
     */
    public function scopeGroupedByInvoice($query)
    {
        return $query
            ->select([
                'supplier_invoice_number',
                DB::raw('SUM(cost) as total_cost'),
                DB::raw('COUNT(*) as receipts_count'),
                DB::raw('MIN(created_at) as first_receipt_at'),
                DB::raw('MAX(created_at) as last_receipt_at'),
                DB::raw('GROUP_CONCAT(DISTINCT booking_id) as booking_ids'),
            ])
            ->whereNotNull('supplier_invoice_number')
            ->where('supplier_invoice_number', '!=', '')
            ->groupBy('supplier_invoice_number')
            ->orderByDesc('last_receipt_at');
    }
}
