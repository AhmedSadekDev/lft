<?php

namespace App\Models;

use App\Traits\FileAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentExpense extends Model
{
    use HasFactory, FileAttributes;

    const generalExpenses = 1;
    const carExpenses = 2;
    protected $guarded = [];

    protected $imageFolder = 'agent_expenses';

    public function getCreatedAtAttribute($value)
    {
        return date('Y-m-d', strtotime($value));
    }
    public function getImageAttribute($value)
    {
        return asset('/storage/' . $this->imageFolder . '/' . $value);
    }

    public function bookingContainer()
    {
        return $this->belongsTo(BookingContainer::class);
    }
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
    
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
    public function getTitleAttribute()
    {
        return ($this->service?->serviceCategory?->title ?? "") . "  -  " . ($this->service?->name ?? "");
    }
    public function delivery_policy()
    {
        return $this->belongsTo(DeliveryPolicy::class);
    }
}
