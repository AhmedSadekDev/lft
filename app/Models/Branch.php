<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'factory_id',
        'name',
        'country_id',
        'country',
        'city_id',
        'city',
        'address',
        'email',
        'number',
    ];

    public function factory()
    {
        return $this->belongsTo(Factory::class);
    }


    public function getFactory()
    {
        return $this->factory()->first();
    }




}
