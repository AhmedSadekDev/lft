<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'service_category_id',
    ];

    public function serviceCategory(){
        return $this->belongsTo(ServiceCategory::class);
    }

    public function company(){
        return $this->belongsTo(Company::class);
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_services', 'company_id', 'service_id')->withTimestamps();
    }
}
