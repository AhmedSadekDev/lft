<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class companyServices extends Pivot
{
    protected $fillable = [
        'company_id',
        'service_id',
        'cost',
    ];



}
