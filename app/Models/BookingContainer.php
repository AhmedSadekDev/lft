<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingContainer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }


    public function extraExpenses()
    {
        return $this->hasMany(BookingContrainerExtraCosts::class, 'booking_container_id');
    }
    
    
    public function bookingPapers()
    {
        return $this->hasMany(BookingPaper::class);
    }
    
    
    
    public function delivery_policies()
    {
        return $this->belongsToMany(
            DeliveryPolicy::class,
            'delivery_policy_containers',
            'booking_container_id',
            'delivery_policy_id'
        )->withTimestamps();
    }


    public function expenses()
    {
        return $this->hasMany(AgentExpense::class);
    }

    public function payingCar()
    {
        return $this->hasOne(Payingcar::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function container()
    {
        return $this->belongsTo(Container::class);
    }

    public function yard()
    {
        return $this->belongsTo(Yard::class);
    }

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class, "booking_container_agents")->withPivot('booking_container_status')->withTimestamps();
    }

    public function getCreatedAtAttribute($value)
    {
        return date('Y-m-d', strtotime($value));
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, "attached_id")->where("attached_type", 'App\Models\BookingContainer');
    }

    public function departure()
    {
        return $this->belongsTo(CitiesAndRegions::class);
    }

    public function aging()
    {
        return $this->belongsTo(CitiesAndRegions::class);
    }

    public function loading()
    {
        return $this->belongsTo(CitiesAndRegions::class);
    }

    public function getContainerTypeAttribute()
    {
        return $this->container->type;
    }

    public function last_movement()
    {
        return $this->hasMany(BookingMovement::class, 'container_id');
    }
}
