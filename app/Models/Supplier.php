<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    /**
     * Alias matching the requested relationship name.
     */
    public function supplierPayments(): HasMany
    {
        return $this->payments();
    }

    public function scopeSearch($query, ?string $term)
    {
        if (filled($term)) {
            $query->where('name', 'like', '%' . $term . '%');
        }

        return $query;
    }
}
