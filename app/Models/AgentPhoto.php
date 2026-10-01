<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentPhoto extends Model
{
    protected $fillable = ['agent_id', 'path', 'original_name'];

    protected $hidden = ['path'];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}
