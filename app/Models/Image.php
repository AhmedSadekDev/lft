<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FileAttributes;

class Image extends Model
{
    use HasFactory,FileAttributes;


    protected $guarded = [];

    public function getImageAttribute($value)
    {
        return asset('/storage/' . $value);
    }

}
