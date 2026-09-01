<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FileAttributes;

class Image extends Model
{
    use HasFactory, FileAttributes;

    protected $guarded = [];

    protected $imageFolder = 'booking_papers';

    public function getImageAttribute($value)
    {
        if (empty($value)) {
            return '';
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        return asset('/storage/' . ltrim($value, '/'));
    }
}
