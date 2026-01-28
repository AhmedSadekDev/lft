<?php

namespace App\Models;

use App\Traits\FileAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivateCompany extends Model
{
    use HasFactory, FileAttributes;

    protected $attachmentFolder = 'private_companies';

    protected $fillable = [
        'name',
        'tax_no',
        'commercial_register',
        'logo',
    ];

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getLogoAttribute($value)
    {
        if ($value) {
            return asset('/storage/' . $this->attachmentFolder . '/' . $value);
        }
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */

    public function setLogoAttribute($value)
    {
        if ($value && is_file($value)) {
            $uploadedFile = $value->storeAs($this->attachmentFolder, generateAttachmentName($value), "public");
            $arrVal = explode('/', $uploadedFile);
            $this->attributes['logo'] = $arrVal[count($arrVal) - 1];
        } elseif (is_string($value)) {
            $this->attributes['logo'] = $value;
        }
    }
}
