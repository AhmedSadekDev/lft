<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingContainerAgent extends Model
{
    use HasFactory;
    
    protected $table = 'booking_container_agents';

    public $timestamps = true;
    protected $guarded = [];

}
