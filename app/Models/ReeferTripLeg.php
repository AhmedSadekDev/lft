<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ReeferTripLeg extends Model
{
    protected $guarded = [];

    protected $casts = [
        'occurred_at' => 'datetime',
        'stage' => 'integer',
    ];

    public function deliveryPolicy(): BelongsTo
    {
        return $this->belongsTo(DeliveryPolicy::class, 'delivery_policy_id');
    }

    public function bookingContainer(): BelongsTo
    {
        return $this->belongsTo(BookingContainer::class, 'booking_container_id');
    }

    public function recordedByAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'recorded_by_agent_id');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
