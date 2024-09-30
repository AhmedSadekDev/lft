<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Note extends Model
{
    use HasFactory;
    protected $table = 'notes';

    protected $guarded = [];

    public function attacher(){
        return $this->belongsTo($this->attacher_type,"attacher_id");
    }

    public function getDateAttribute(){
        return $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d') : "";
    }

    public function getTimeAttribute(){
        return $this->created_at ? Carbon::parse($this->created_at)->format('g:i a') : "";
    }
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
