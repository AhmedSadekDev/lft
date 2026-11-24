<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReeferTrip extends Model
{
    protected $guarded = [];

    protected $casts = [
        'trip_date' => 'date',
        'custody_egp' => 'float',
        'custody_usd' => 'float',
        'exit_port_at' => 'datetime',
        'arrive_departure_port_at' => 'datetime',
        'ferry_booking_at' => 'datetime',
        'ferry_boarding_at' => 'datetime',
        'arrive_saudi_at' => 'datetime',
        'exit_saudi_at' => 'datetime',
        'arrive_final_customs_at' => 'datetime',
        'exit_final_customs_at' => 'datetime',
        'arrive_warehouse_at' => 'datetime',
        'cleared_at' => 'datetime',
        'invoice_date' => 'date',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(ReeferShipment::class, 'reefer_shipment_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function deliveryPolicy(): BelongsTo
    {
        return $this->belongsTo(DeliveryPolicy::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(ContainerFollowUp::class, 'reefer_trip_id')->orderByDesc('occurred_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Timeline fields mapped from Hi-Freight sheet columns. */
    public static function timelineFields(): array
    {
        return [
            'exit_port_at' => 'خروج الميناء',
            'arrive_departure_port_at' => 'وصول ميناء المغادرة',
            'ferry_booking_at' => 'حجز العبارة',
            'ferry_boarding_at' => 'ركوب العبارة',
            'arrive_saudi_at' => 'وصول السعودية',
            'exit_saudi_at' => 'خروج من السعودية',
            'arrive_final_customs_at' => 'وصول جمرك الدولة النهائي',
            'exit_final_customs_at' => 'خروج جمرك الدولة النهائي',
            'arrive_warehouse_at' => 'وصول المخزن',
            'cleared_at' => 'تعتيق',
        ];
    }
}
