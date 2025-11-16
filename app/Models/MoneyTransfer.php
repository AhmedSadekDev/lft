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
    const officeCommission = 5; // دخان المكتب

    protected $guarded = [];

    public function transferer()
    {
        return $this->morphTo();
    }
    public function transfered()
    {
        return $this->morphTo();
    }
    
    public function delivery_policy()
    {
        return $this->belongsTo(DeliveryPolicy::class);
    }

    public function getCreatedAtAttribute($value)
    {
        return date('Y-m-d', strtotime($value));
    }
    public function getTitleAttribute()
    {
        if ($this->type == self::officeCommission) {
            return __('main.office_commission') . '   -   ' . ($this->transfered?->name ?? "");
        }
        
        if ($this->type == self::settle) {
            return __('main.settle_delivery_policy') . '   -   ' . ($this->transfered?->name ?? "");
        }
        
        if ($this->type == self::deliveryPolicy) {
            return __('main.custody_transfer') . '   -   ' . ($this->transfered?->name ?? "");
        }
        
        if ($this->type == self::transferAgent) {
            return __('main.transfer_to_agent') . '   -   ' . ($this->transfered?->name ?? "");
        }
        
        return __('main.custody_transfer') . '   -   ' . ($this->transfered?->name ?? "");
    }

}
