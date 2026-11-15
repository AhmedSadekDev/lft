<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyAccountAdjustment extends Model
{
    public const TYPE_DISCOUNT = 'discount';

    public const TYPE_ADDITION = 'addition';

    protected $fillable = [
        'company_id',
        'type',
        'amount',
        'currency',
        'reason',
        'note_number',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDiscount(): bool
    {
        return ($this->type ?? self::TYPE_DISCOUNT) === self::TYPE_DISCOUNT;
    }

    public function isAddition(): bool
    {
        return ($this->type ?? '') === self::TYPE_ADDITION;
    }

    /** Positive increases AR; negative reduces AR. */
    public function signedAmount(): float
    {
        $amount = abs((float) $this->amount);

        return $this->isAddition() ? $amount : -$amount;
    }

    public function typeLabel(): string
    {
        return $this->isAddition() ? 'إشعار إضافة' : 'إشعار خصم';
    }
}
