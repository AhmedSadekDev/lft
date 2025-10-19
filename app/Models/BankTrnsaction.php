<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTrnsaction extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeNameAttribute()
    {
        return $this->type == 1 ? __('main.credit') : __("main.debit");
    }
    public function typeText()
    {
        if($this->type == 0){
            return __('main.debit');
        } elseif($this->type == 1){
            return __('main.credit');
        } else{
            return __('main.transfer');
        }
    }
}
