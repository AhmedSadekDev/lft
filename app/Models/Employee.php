<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'job',
        'email',
        'phone',
        'note',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getCompany()
    {
        return $this->company()->first();
    }


}
