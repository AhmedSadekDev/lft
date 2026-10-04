<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentPhoto extends Model
{
    protected $fillable = [
        'agent_id',
        'path',
        'original_name',
        'booking_id',
        'booking_container_id',
        'booking_paper_id',
        'assigned_at',
    ];

    protected $hidden = ['path'];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingContainer()
    {
        return $this->belongsTo(BookingContainer::class);
    }

    public function bookingPaper()
    {
        return $this->belongsTo(BookingPaper::class);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('booking_id');
    }

    public function scopeAssigned($query)
    {
        return $query->whereNotNull('booking_id');
    }
}

