<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Yard extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'title'
    ];

    public $translatable = ['title'];

    public function getCreatedAtAttribute($value)
    {
        return date('Y-m-d', strtotime($value));
    }
//    public function bookingContainers() :HasMany{
//        return $this->hasMany(BookingContainer::class);
//    }
    public function bookingContainers()
    {
        return $this->hasManyThrough(BookingContainer::class, Booking::class);
    }
    public function booking(){
        return $this->hasMany(Booking::class);
    }

}
