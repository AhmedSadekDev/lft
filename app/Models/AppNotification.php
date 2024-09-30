<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AppNotification extends Model
{
    use HasFactory;
    protected $table = 'app_notifications';

    protected $guarded = [];

    protected $appends = ['date','time',];

    const specific = 1;
    const all = 2;
    public function notificationable(){
        return $this->belongsTo($this->notificationable_type,"notificationable_id");
    }
    public function getDateAttribute(){
        return $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d') : "";
    }

    public function getTimeAttribute(){
        return $this->created_at ? Carbon::parse($this->created_at)->format('g:i a') : "";
    }

   


}
