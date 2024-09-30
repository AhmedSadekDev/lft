<?php

namespace App\Models;

use App\Traits\FileAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;

class Agent extends  Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, FileAttributes;

    protected $table = "agents";
    protected $guarded = [];

    protected $imageFolder = 'agents';

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
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
    public function getImageAttribute($value)
    {
        return asset('/storage/' . $this->imageFolder . '/' . $value);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(AgentExpense::class);
    }

    public function agentCarTransfers(): HasMany
    {
        return $this->hasMany(AgentCarTranfer::class);
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
    public function booking_containers(): HasMany
    {
        return $this->hasMany(BookingContainerAgent::class);
    }
    public function getNumberOfBookingsAttribute()
    {
        $number_of_bookings = count($this->booking_containers()->whereDate("created_at", now())->get());
        return $number_of_bookings;
    }
    public function agent_booking_containers(): BelongsToMany
    {
        return $this->belongsToMany(BookingContainer::class, "booking_container_agents", "agent_id", "booking_container_id")->withPivot("booking_container_status","created_at")->withTimestamps();
    }
    public function scopeOfFilter($query){
        return $query->when(request()->word != null ,function($q){
           $q->where("name", "like", "%" . request()->word . "%")
                ->orWhere("email", "like", "%" . request()->word . "%")
                ->orWhere("phone", "like", "%" . request()->word . "%");
            });
}

}
