<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payingcar extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];
    
    public function car()
    {
        return $this->belongsTo(Car::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function moneyTransfers()
    {
        return $this->MorphMany(MoneyTransfer::class, "transfered");
    }
    
    
}
