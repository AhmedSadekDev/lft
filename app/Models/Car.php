<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function getCreatedAtAttribute($value)
    {
        return date('Y-m-d', strtotime($value));
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }
    
    public function deliveryPolicies()
    {
        return $this->hasMany(DeliveryPolicy::class);
    }
    public function payingcars()
    {
        return $this->hasMany(Payingcar::class);
    }

    /** Preserve the listing's contains and SQL wildcard semantics. */
    public function scopeSearchListing($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('car_number', 'like', "%{$search}%");
        });
    }
}
