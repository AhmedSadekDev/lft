<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingContrainerExtraCosts extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];


    public function booking_container()
    {
        return $this->belongsTo(BookingContainer::class);
    }


    public function car()
    {
        return $this->belongsTo(Car::class);
    }


    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
    
}
