<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'craeted_at', 'updated_at'];

    public function bankTrnsaction()
    {
        return $this->hasMany(BankTrnsaction::class);
    }


    public function balance()
    {
        return $this->bankTrnsaction->where('type', 1)->sum('amount') - $this->bankTrnsaction->where('type', 0)->sum('amount');
    }
}
