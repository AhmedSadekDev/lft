<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SupplierPayment extends Model
{
    use HasFactory;

    public const SOURCE_SAFE = 'safe';
    public const SOURCE_REPRESENTATIVE = 'representative';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Representative (agent) when source_type = representative.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'source_id');
    }

    public function moneyTransfers(): MorphMany
    {
        return $this->morphMany(MoneyTransfer::class, 'transfered');
    }

    public function scopeForSupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function isFromSafe(): bool
    {
        return $this->source_type === self::SOURCE_SAFE;
    }

    public function isFromRepresentative(): bool
    {
        return $this->source_type === self::SOURCE_REPRESENTATIVE;
    }
}
