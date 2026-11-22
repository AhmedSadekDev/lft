<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsdVaultTransaction extends Model
{
    public const DIR_IN = 'in';
    public const DIR_OUT = 'out';

    public const CAT_OPENING = 'opening_balance';
    public const CAT_DEPOSIT = 'deposit';
    public const CAT_WITHDRAWAL = 'withdrawal';
    public const CAT_AGENT_CUSTODY = 'agent_custody';
    public const CAT_BANK_TRANSFER = 'bank_transfer';
    public const CAT_POLICY_CUSTODY = 'policy_custody';
    public const CAT_EXPENSE = 'expense';
    public const CAT_REVERSAL = 'reversal';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
        'occurred_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function vault(): BelongsTo
    {
        return $this->belongsTo(UsdVault::class, 'usd_vault_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
