<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'container_id',
        'status',
        'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

}
