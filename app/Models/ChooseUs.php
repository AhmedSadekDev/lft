<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FileAttributes;
use Spatie\Translatable\HasTranslations;

class ChooseUs extends Model
{
    use HasFactory, FileAttributes, HasTranslations;
    protected $imageFolder = 'chooseUs';

    protected $fillable = [
        'title',
        'description',
        'image',
    ];

    public $translatable = ['title', 'description'];

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */
    public function getImageAttribute($value)
    {
        return asset('/storage/'.$this->imageFolder . '/' . $value);
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */

    // protected function title(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn ($value) => json_decode($value),
    //         set: fn ($value) => json_encode([ 'ar' => $value->ar_title, 'en' => $value->en_title ]),
    //     );
    // }

}
