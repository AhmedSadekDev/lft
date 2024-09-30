<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyBookingContainer extends Model
{
    use HasFactory;
    protected $table = 'daily_booking_containers';

    protected $guarded = [];
    
    public function booking_container()
    {
        return $this->belongsTo(BookingContainer::class,"booking_container_id");
    }

}
