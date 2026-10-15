<?php

namespace App\Models;

use App\Traits\FileAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentExpense extends Model
{
    use HasFactory, FileAttributes, \Illuminate\Database\Eloquent\SoftDeletes;

    public const DELETED_AT = 'voided_at';

    const generalExpenses = 1;
    const carExpenses = 2;
    protected $guarded = [];

    protected $attributes = ['version' => 1];

    protected $casts = ['version' => 'integer', 'type_id' => 'integer'];

    protected $imageFolder = 'agent_expenses';

    protected static function booted(): void
    {
        static::saving(function (AgentExpense $expense) {
            if ($expense->booking_container_id) {
                $bookingId = BookingContainer::query()
                    ->whereKey($expense->booking_container_id)
                    ->value('booking_id');
                if ($bookingId !== null) {
                    $expense->booking_id = $bookingId;
                }
            } elseif ($expense->delivery_policy_id) {
                $policy = DeliveryPolicy::query()
                    ->with(['booking_containers:id,booking_id'])
                    ->find($expense->delivery_policy_id);
                $container = $policy?->booking_containers->first();
                if ($container) {
                    $expense->booking_container_id = $expense->booking_container_id ?: $container->id;
                    $expense->booking_id = $container->booking_id;
                }
            }
        });
    }

    public function getCreatedAtAttribute($value)
    {
        return date('Y-m-d', strtotime($value));
    }
    

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
    public function bookingContainer()
    {
        return $this->belongsTo(BookingContainer::class);
    }
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function getResponsibleNameAttribute(): ?string
    {
        return $this->agent?->name ?: $this->bookingService?->responsible_name;
    }
    
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function bookingService(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BookingService::class);
    }
    public function getTitleAttribute()
    {
        return ($this->service?->serviceCategory?->title ?? "") . "  -  " . ($this->service?->name ?? "");
    }
    public function delivery_policy()
    {
        return $this->belongsTo(DeliveryPolicy::class);
    }

    public function scopeForBooking($query, int $bookingId)
    {
        return $query->where(function ($q) use ($bookingId) {
            $q->where('booking_id', $bookingId)
                ->orWhereHas('bookingContainer', function ($containerQuery) use ($bookingId) {
                    $containerQuery->where('booking_id', $bookingId);
                });
        });
    }

    /**
     * مصروفات التطبيق فقط — استبعاد ما أُنشئ من الداشبورد مربوطًا بـ BookingService
     * حتى لا يظهر نفس الإيصال مرتين في الطلب/الفاتورة.
     */
    public function scopeStandaloneFromBookingService($query)
    {
        return $query->whereNull('booking_service_id');
    }

    public function scopeVisibleToAgent($query)
    {
        return $query->whereDoesntHave('booking.invoice')
            ->whereDoesntHave('bookingContainer.booking.invoice')
            ->whereDoesntHave('delivery_policy.booking_containers.booking.invoice');
    }

    /**
     * إيصالات التطبيق التي لم تُراجع بعد. المراجعة = admin_approval، أو إقفال إيصالات المرحلة،
     * أو تسوية العهدة، أو إصدار فاتورة؛ وهي نفس الحالات التي يُمنع فيها المندوب من التعديل.
     */
    public function scopeAwaitingReview($query)
    {
        return $query->visibleToAgent()
            ->whereNull('booking_service_id')
            ->where(fn ($q) => $q->where('admin_approval', 0)->orWhereNull('admin_approval'))
            ->whereDoesntHave('delivery_policy', fn ($q) => $q->where('is_settled', 1))
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')->from('booking_container_stages')
                    ->whereColumn('booking_container_stages.booking_container_id', 'agent_expenses.booking_container_id')
                    ->whereColumn('booking_container_stages.type_id', 'agent_expenses.type_id')
                    ->whereNotNull('booking_container_stages.receipts_closed_at');
            });
    }

    /**
     * شاشة مصروفات المندوب: إيصالاته تبقى حتى المراجعة، وخصومات الداشبورد تظهر يومها فقط كما كانت.
     */
    public function scopeOnAgentScreen($query)
    {
        return $query->where(fn ($q) => $q->where(fn ($open) => $open->awaitingReview())
            ->orWhere(fn ($dashboard) => $dashboard->whereNotNull('booking_service_id')->visibleToAgent()->whereDate('created_at', now())));
    }
}
