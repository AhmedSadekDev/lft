<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContainerFollowUp extends Model
{
    protected $guarded = [];

    protected $casts = [
        'occurred_at' => 'datetime',
        'recorded_at' => 'datetime',
    ];

    public function bookingContainer(): BelongsTo
    {
        return $this->belongsTo(BookingContainer::class);
    }

    public function deliveryPolicy(): BelongsTo
    {
        return $this->belongsTo(DeliveryPolicy::class);
    }

    public function reeferTrip(): BelongsTo
    {
        return $this->belongsTo(ReeferTrip::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
