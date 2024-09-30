<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;

class Superagent extends  Authenticatable implements JWTSubject
{
    use HasFactory,Notifiable;

    protected $table = "superagents";
    protected $guarded = [];


    public function getJWTIdentifier() {
        return $this->getKey();
    }

    public function getJWTCustomClaims() {
        return [];
    }

    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = !is_null($password) ? Hash::make($password) : (!is_null($this->password) ? $this->password : null);
    }
    public function getCreatedAtAttribute($value)
    {
        return date('Y-m-d', strtotime($value));
    }

    public function getUpdatedAtAttribute($value)
    {
        return date('Y-m-d', strtotime($value));
    }
    public function booking_containers(): HasMany
    {
        return $this->hasMany(DailyBookingContainer::class,"superagent_id");
    }
    public function superagent_booking_containers(): BelongsToMany
    {
        return $this->belongsToMany(BookingContainer::class, "daily_booking_containers", "superagent_id", "booking_container_id")->withPivot("booking_container_status","created_at")->withTimestamps();
    }
    
    public function transactions()
    {
        return $this->hasMany(superagentTransaction::class);
    }
    
    public function financial_custodies(): MorphMany
    {
        return $this->MorphMany(MoneyTransfer::class, "transfered");
    }
    public function sended_financial_custodies(): MorphMany
    {
        return $this->MorphMany(MoneyTransfer::class, "transferer");
    }
    public function getTotalFinancialCustodyAttribute(): int
    {
        $financial_custodies = $this->financial_custodies()->whereDate("created_at", now())->sum("value");

        return $financial_custodies;
    }

    public function getSpentedFinancialCustodyAttribute(): int
    {
        $financial_custodies = $this->sended_financial_custodies()->whereDate("created_at", now())->sum("value");
        $expenses = $this->expenses()->whereDate("created_at", now())->sum("value");

        return $financial_custodies + $expenses;
    }
    public function getRemainingFinancialCustodyAttribute(): int
    {
        return   $this->total_financial_custody - $this->spented_financial_custody;
    }
}

