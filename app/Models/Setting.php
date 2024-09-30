<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FileAttributes;
use Illuminate\Support\Str;

class Setting extends Model
{
    use HasFactory, FileAttributes;
    protected $logoFolder = 'settings';

    protected $fillable =[
        'phone',
        'email',
        'whatsapp',
        'facebook',
        'twitter',
        'linkedin',
        'instagram',
        'logo'
    ];


    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
    public function getLogoAttribute($value)
    {
        return asset('/storage/'.$this->logoFolder . '/' . $value);
    }

}
