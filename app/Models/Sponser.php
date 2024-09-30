<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FileAttributes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Sponser extends Model
{
    use HasFactory, FileAttributes, HasTranslations;

    protected $imageFolder = 'sponsers';

    protected $fillable = [
        'title',
        'description',
        'image',
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
