<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * حاويات لا تزال في الساحة قبل إتمام التحميل (0 تخصيص، 1 تحميل).
     * بعد تأكيد التحميل status = 2 فلا تُحسب ولا تُعرض في قوائم الساحة.
     */
    public function scopeWithActiveYardContainersCount(Builder $query): Builder
    {
        return $query
            ->select('yards.*')
            ->selectSub(function ($sub) {
                $sub->selectRaw('COUNT(*)')
                    ->from('booking_containers')
                    ->join('bookings', 'booking_containers.booking_id', '=', 'bookings.id')
                    ->whereColumn('bookings.yard_id', 'yards.id')
                    ->whereIn('booking_containers.status', [0, 1]);
            }, 'active_containers_count');
    }

}
