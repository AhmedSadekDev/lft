<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPaper extends Model
{
    use HasFactory;
    const specification = 0;
    const loading = 1;
    const unloading = 2;
    const carPaper = 3;
    protected $guarded = [];


    public function booking():BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }
    
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}
