<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeliveryPolicy extends Model
{
    use HasFactory;
    protected $guarded = [];


    public function booking_containers()
    {
        return $this->belongsToMany(
            BookingContainer::class,
            'delivery_policy_containers',
            'delivery_policy_id',
            'booking_container_id'
        )->withTimestamps();
    }


    public function extraExpenses()
    {
        return $this->hasMany(BookingContrainerExtraCosts::class, 'delivery_policy_id');
    }

    public function payingCars()
    {
        return $this->hasMany(Payingcar::class);
    }

   

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }
    
    public function car_expenses(): HasMany
    {
        return $this->hasMany(AgentExpense::class);
    }
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
    public function money_transfer(): HasOne
    {
        return $this->hasOne(MoneyTransfer::class)->whereType(3);
    }
    public function settled_money_transfer(): HasOne
    {
        return $this->hasOne(MoneyTransfer::class)->whereType(4);
    }
    public function getCreatedAtAttribute($value)
    {
        return date('Y-m-d', strtotime($value));
    }
    public function getDriverDuesAttribute(){
        return  ($this?->money_transfer?->value) - ($this->car_expenses->sum("value"));
    }
}
