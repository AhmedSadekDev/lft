<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\Translatable\HasTranslations;

class shippingAgent extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'title',
        'description',
        'image'
    ];

    public $translatable = ['title', 'description'];

    public function Bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
    public function bookingContainers(): HasManyThrough
    {
        return $this->hasManyThrough(BookingContainer::class, Booking::class, 'shipping_agent_id', 
        'booking_id');
    }
}
