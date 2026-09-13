<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingContainerStage extends Model
{
    protected $guarded = ['id'];

    protected $attributes = ['version' => 1];

    protected $casts = ['receipts_closed_at' => 'datetime', 'version' => 'integer', 'type_id' => 'integer'];
}
