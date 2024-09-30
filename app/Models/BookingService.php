<?php

namespace App\Models;

use App\Traits\FileAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingService extends Model
{
    use HasFactory, FileAttributes;
    protected $imageFolder = 'services';
    protected $fillable = [
        'booking_id',
        'service_id',
        'note',
        'price',
        'image',
        'service_data', // TODO: HANDLE WHEN THESE SHLD BE ADDED
    ];

    public function getImageAttribute($value)
    {
        return asset('/storage/' . $this->imageFolder . '/' . $value);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /** DEPRECATED: Get it through the service.service_category instead */
    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function getFullNameAttribute()
    {
        return $this->service->name
            . ' '
            . $this->service->serviceCategory->title;
    }
}
