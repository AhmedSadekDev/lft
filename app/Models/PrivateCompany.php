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
        'phone1',
        'phone2',
        'tel_fax',
        'email',
        'address',
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

    /** Preserve the listing's contains and SQL wildcard semantics. */
    public function scopeSearchListing($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('tax_no', 'like', "%{$search}%")
                ->orWhere('commercial_register', 'like', "%{$search}%");
        });
    }

    /** Explicit legacy-column allowlist; invalid input resets the entire order. */
    public function scopeSortListing($query, $column = 'id', $direction = 'desc')
    {
        $allowed = [
            'id', 'name', 'tax_no', 'commercial_register', 'logo', 'phone1', 'phone2', 'tel_fax',
            'email', 'address', 'created_at', 'updated_at',
        ];
        $direction = is_string($direction) ? strtolower($direction) : null;
        if (!is_string($column) || !in_array($column, $allowed, true)
            || !in_array($direction, ['asc', 'desc'], true)) {
            $column = 'id';
            $direction = 'desc';
        }

        return $query->orderBy($column, $direction);
    }
}
