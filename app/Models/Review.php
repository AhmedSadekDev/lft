<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FileAttributes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Review extends Model
{
    use HasFactory, FileAttributes, HasTranslations;

    protected $imageFolder = 'services';

    protected $fillable = [
        'title',
        'description',
        'image',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $translatable = ['title', 'description'];

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getImageAttribute($value)
    {
        return asset('/storage/'.$this->imageFolder . '/' . $value);
    }
}
