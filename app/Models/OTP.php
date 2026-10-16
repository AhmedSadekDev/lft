<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OTP extends Model
{
    protected $table = 'otps';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'agent_id',
        'superagent_id',
        'otp',
        'expire_at',
        'employee_id',
        'reset_token_hash',
        'reset_token_expires_at',
        'reset_token_used_at',
    ];

    protected $casts = [
        'expire_at' => 'datetime',
        'reset_token_expires_at' => 'datetime',
        'reset_token_used_at' => 'datetime',
    ];

    protected $hidden = [
        'otp',
        'reset_token_hash',
    ];

    public const  AddToPermission = false;
    
    public function employee(){
        return $this->belongsTo(Employee::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function company(){
        return $this->belongsTo(Company::class);
    }

    public function agent() :BelongsTo{
        return $this->belongsTo(Agent::class);
    }
    public function superagent() :BelongsTo{
        return $this->belongsTo(Superagent::class);
    }
}
