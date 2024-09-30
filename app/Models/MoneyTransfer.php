<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoneyTransfer extends Model
{
    use HasFactory;

    const fromDashboard = 1;
    const transferAgent = 2;

    const deliveryPolicy = 3;

    const settle = 4;

    protected $guarded = [];

    public function transferer()
    {
        return $this->morphTo();
    }
    public function transfered()
    {
        return $this->morphTo();
    }

    public function getCreatedAtAttribute($value)
    {
        return date('Y-m-d', strtotime($value));
    }
    public function getTitleAttribute()
    {
        return __('main.custody_transfer') . '   -   ' . $this->transfered?->name ?? "";
    }

}
