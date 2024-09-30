<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LogActivity extends Model
{
    use HasFactory;
    protected $table = 'log_activities';

    protected $guarded = [];

    protected $appends = ['date','time','action'];
    public function attacher(){
        return $this->belongsTo($this->attacher_type,"attacher_id");
    }

    public function log(){
        return $this->belongsTo($this->log_type,"log_id");
    }

    public function getDateAttribute(){
        return $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d') : "";
    }

    public function getTimeAttribute(){
        return $this->created_at ? Carbon::parse($this->created_at)->format('g:i a') : "";
    }

    public function getActionAttribute()
    {
        $value = $this->log?->value ?? 0;
        $container_no = $this->log?->container_no ?? "";
        $name = $this->log_type == MoneyTransfer::class ? $this->log?->transfered?->name : "";

        if ($this->log_type == AgentExpense::class) {
            $msg = __("main.spend_money", ['value' => $value]);
            return $msg;
        }
        elseif ($this->log_type == MoneyTransfer::class) {
           $msg =  __("main.transfer_money", ['value' => $value, 'name' => $name]);
            return $msg;
        }
        elseif ($this->log_type == BookingContainer::class) {

            return match ($this->log?->status) {
                1 =>  __("main.agent_done_specification", ['container_no' => $container_no]),
                2 =>  __("main.agent_done_loading", ['container_no' => $container_no]),
                3 =>__("main.agent_done_unloading", ['container_no' => $container_no]),
                default =>  __("main.unknown_container_status", ['container_no' => $container_no]),
            };
        }
    }


}
