<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UsdVault extends Model
{
    protected $guarded = [];

    protected $casts = [
        'opening_balance' => 'float',
        'balance' => 'float',
        'is_active' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(UsdVaultTransaction::class);
    }

    public static function primary(): ?self
    {
        return static::query()->where('is_active', 1)->orderBy('id')->first()
            ?? static::query()->orderBy('id')->first();
    }
}
