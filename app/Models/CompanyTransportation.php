<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyTransportation extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function container()
    {
        return $this->belongsTo(Container::class);
    }

    public function Departure()
    {
        return $this->belongsTo(CitiesAndRegions::class);
    }

    public function Loading()
    {
        return $this->belongsTo(CitiesAndRegions::class);
    }

    public function Aging()
    {
        return $this->belongsTo(CitiesAndRegions::class);
    }

    public function getCompanyNameAttribute()
    {
        return $this->company?->name;
    }

    public function getContainerTypeAttribute()
    {
        return $this->container?->full_name;
    }

    public function scopeFilterTransportation($query, $data)
    {

        $result = $query->when(isset($data['departure_id']) && !is_null($data['departure_id']), function ($query) use ($data) {
            // dd('test123');
            return $query->where('departure_id', $data['departure_id']);
        })->when(isset($data['loading_id']) && !is_null($data['loading_id']), function ($query) use ($data) {
            // dd('test1234');
            return $query->where('loading_id', $data['loading_id']);
        })->when(isset($data['aging_id']) && !is_null($data['aging_id']), function ($query) use ($data) {
            // dd('test12345');
            return $query->where('aging_id', $data['aging_id']);
        });
    }
}
