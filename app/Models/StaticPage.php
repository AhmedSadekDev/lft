<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FileAttributes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class StaticPage extends Model
{
    use HasFactory, FileAttributes, HasTranslations;
    protected $imageFolder = 'staticPages';

    protected $fillable = [
        'key',
        'title',
        'description',
        'image'
    ];

    public $translatable = ['title', 'description'];
    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */

    public function setKeyAttribute($value)
    {
        $key= trim_key($value);
        $this->attributes['key'] = $key;
    }

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
