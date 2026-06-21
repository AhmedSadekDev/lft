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

    protected static function booted(): void
    {
        static::saving(function (AgentExpense $expense) {
            if ($expense->booking_container_id) {
                $bookingId = BookingContainer::query()
                    ->whereKey($expense->booking_container_id)
                    ->value('booking_id');
                if ($bookingId !== null) {
                    $expense->booking_id = $bookingId;
                }
            } elseif ($expense->delivery_policy_id) {
                $policy = DeliveryPolicy::query()
                    ->with(['booking_containers:id,booking_id'])
                    ->find($expense->delivery_policy_id);
                $container = $policy?->booking_containers->first();
                if ($container) {
                    $expense->booking_container_id = $expense->booking_container_id ?: $container->id;
                    $expense->booking_id = $container->booking_id;
                }
            }
        });
    }

    public function getCreatedAtAttribute($value)
    {
        return date('Y-m-d', strtotime($value));
    }
    

    public function booking()
    {
        return $this->belongsTo(Booking::class);
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
