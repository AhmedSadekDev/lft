<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VaultTransaction extends Model
{
    use HasFactory;
    
    public $guarded = ['id', 'created_at', 'updated_at'];
    
    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
    public function agient()
    {
        return $this->belongsTo(Agent::class);
    }
}
