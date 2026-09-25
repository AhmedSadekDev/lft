<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Mappers\ServiceCategoryStatusMapper;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // protected $appends = ['taxedInvoice'];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */




    public function expenses()
    {
        // مصروفات مرتبطة بالحجز عبر booking_id (يُعبَّأ من التطبيق/الداش من الحاوية أو من بوليصة التوصيل)
        return $this->hasMany(AgentExpense::class, 'booking_id', 'id');
    }

    public function loadExpensesForDisplay(): self
    {
        $this->setRelation(
            'expenses',
            AgentExpense::forBooking($this->id)
                ->standaloneFromBookingService()
                ->with(['service.serviceCategory'])
                ->orderBy('id')
                ->get()
        );

        return $this;
    }



    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    public function factory()
    {
        return $this->belongsTo(Factory::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * Exclude bookings that already have an invoice (hidden from agent/superagent ops lists).
     */
    public function scopeWithoutInvoice($query)
    {
        return $query->whereDoesntHave('invoice');
    }

    public function last_movements()
    {
        return $this->hasMany(BookingMovement::class);
    }

    public function bookingContainers()
    {
        return $this->hasMany(BookingContainer::class);
    }

    public function bookingServices()
    {
        return $this->hasMany(BookingService::class, 'booking_id');
    }

    public function shippingAgent()
    {
        return $this->belongsTo(shippingAgent::class);
    }

    public function containers()
    {
        return $this->belongsToMany(
            Container::class,
            'booking_containers',
            'booking_id',
            'container_id'
        )
            ->withPivot(
                'booking_id',
                'container_id'
            )->withTimestamps();
    }


    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */
    public function getBookingContainers()
    {
        return $this->bookingContainers()->with('container')->get();
    }

    public function getTaxedInvoiceAttribute($value)
    {
        return ($this->company->taxed == 0 ? __('admin.no') : __('admin.yes'));
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** DEPRECATED: use call "transportation_total_price" instead */
    public function getFullPriceBeforeTaxAttribute()
    {
        return $this
            ->bookingContainers()
            ->select(DB::raw('SUM(price) as full_price_before_tax'))
            ->first()
            ->full_price_before_tax
            ?? 0;
    }

    public function getArrivalDateAttribute()
    {
        $arrival_date = $this
            ->bookingContainers()
            ->orderBy('arrival_date', 'desc')
            ->first()
            ?->arrival_date;
        return $arrival_date;
    }

    private function filterServicesUponServiceCategoryStatus(
        int $service_status = ServiceCategoryStatusMapper::TAXED
    ) {
        return $this
            ->bookingServices()
            ->whereHas('service.serviceCategory', function (Builder $query) use ($service_status) {
                return $query
                    ->where(
                        'service_categories.service_status',
                        $service_status
                    );
            });
    }

    ////////////// GET SERVICES FILTERED //////////////

    public function taxed_services()
    {
        return $this
            ->filterServicesUponServiceCategoryStatus(
                ServiceCategoryStatusMapper::TAXED
            );
    }

    public function untaxed_services()
    {
        return $this
            ->filterServicesUponServiceCategoryStatus(
                ServiceCategoryStatusMapper::UNTAXED
            );
    }

    public function not_invoiced_services()
    {
        return $this
            ->filterServicesUponServiceCategoryStatus(
                ServiceCategoryStatusMapper::NOT_INVOICED
            );
    }

    public function getTaxedServices()
    {
        return $this
            ->filterServicesUponServiceCategoryStatus(
                ServiceCategoryStatusMapper::TAXED
            );
    }

    public function getUntaxedServices()
    {
        return $this
            ->filterServicesUponServiceCategoryStatus(
                ServiceCategoryStatusMapper::UNTAXED
            );
    }

    public function getNonInvoicedServices()
    {
        return $this
            ->filterServicesUponServiceCategoryStatus(
                ServiceCategoryStatusMapper::NOT_INVOICED
            );
    }

    ///////////// GET TOTALS ////////////////

    private function getObjectsTotalPrice($query): float
    {
        return $query
            ->select(DB::raw('SUM(price) as total'))
            ->first()
            ->total
            ?? 0;
    }

    public function getTransportationTotalPriceAttribute()
    {
        // $x = $this->expenses->sum('value') ;
        // $y = 0;

        // foreach($this->bookingContainers as $container) {
        //     foreach($container->delivery_policies as $policy) {
        //         $y += $policy->money_transfer->value;
        //     }
        // }

        // return $x + $y;

        return $this->bookingContainers->sum('price');
    }

    public function getTaxedServicesTotalPriceAttribute()
    {
        return $this->calculateTotalPrice($this->taxed_services(), 0);
    }

    public function getUntaxedServicesTotalPriceAttribute()
    {
        $x = $this->calculateTotalPrice($this->untaxed_services(), 1);

        // مصروفات التطبيق فقط — المربوطة بـ booking_service_id محسوبة أصلًا ضمن خدمات الطلب
        $y = $this->expenses()
            ->standaloneFromBookingService()
            ->whereHas('service', function ($query) {
                $query->whereHas('serviceCategory', function ($query) {
                    $query->where('service_status', 1);
                });
            })->sum('value');

        return $x + $y;
    }

    private function calculateTotalPrice($services, $type)
    {
        $x = $this->getObjectsTotalPrice($services);

        return $x;
    }

    public function getNotInvoicedServicesTotalPriceAttribute()
    {
        return $this
            ->getObjectsTotalPrice(
                $this->not_invoiced_services()
            );
    }
    public function yard()
    {
        return $this->belongsTo(Yard::class);
    }
    public function scopeFilterDate(Builder $query, ?string $date)
    {
        $query->when($date, function (Builder $query) use ($date) {
            $query->whereDate('created_at', $date);
        });
    }

    public function scopeFilterDateRange(Builder $query, ?string $dateFrom, ?string $dateTo)
    {
        $query->when($dateFrom || $dateTo, function (Builder $query) use ($dateFrom, $dateTo) {
            if ($dateFrom && $dateTo) {
                $query->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
            } elseif ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            } elseif ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            }
        });
    }
    public function scopeFilterSearch(Builder $query, ?string $search)
    {
        $query->when($search, function (Builder $query) use ($search) {
            $query->whereHas('bookingContainers', function (Builder $q) use ($search) {
                $q->where('container_number', 'like', "%$search%");
            });
        });
    }
    public function scopeFilterCompany(Builder $query, ?string $company)
    {
        $query->when($company, function (Builder $query) use ($company) {
            $query->where('company_id', $company);
        });
    }
    public function scopeFilterTaxStatus(Builder $query, ?int $tax_status = null)
    {
        $query->when($tax_status, function (Builder $query) use ($tax_status) {
            $query->where('taxed', $tax_status);
        });
    }

    public function scopeFilterStage(Builder $query, ?string $stage)
    {
        $query->when($stage && $stage !== 'all', function (Builder $query) use ($stage) {
            switch ($stage) {
                case 'assigned':
                case 'specification':
                case '0':
                    $query->whereHas('bookingContainers', function ($q) {
                        $q->where(function ($sub) {
                            $sub->where('status', 0)
                                ->orWhere(function ($q2) {
                                    $q2->where('status', 1)->where('superagent_specification_approved', 0);
                                });
                        });
                    });
                    break;

                case 'waiting':
                case 'waiting_loading':
                    $query->whereHas('bookingContainers', function ($q) {
                        $q->where('superagent_specification_approved', 1)
                          ->where('is_in_loading', 0)
                          ->where('superagent_loading_approved', 0);
                    });
                    break;

                case 'loading':
                case '1':
                    $query->whereHas('bookingContainers', function ($q) {
                        $q->where('superagent_specification_approved', 1)
                          ->where('is_in_loading', 1)
                          ->where('superagent_loading_approved', 0);
                    });
                    break;

                case 'unloading':
                case 'discharge':
                case '2':
                    $query->whereHas('bookingContainers', function ($q) {
                        $q->where('superagent_loading_approved', 1)
                          ->where('superagent_unloading_approved', 0);
                    });
                    break;

                case 'invoiced':
                case 'closed':
                case 'finished':
                case '3':
                    $query->where(function ($sub) {
                        $sub->whereHas('invoice')
                            ->orWhereHas('bookingContainers', function ($qc) {
                                $qc->where('superagent_unloading_approved', 1);
                            });
                    });
                    break;
            }
        });
    }

    public function getStageInfoAttribute(): array
    {
        $containers = $this->relationLoaded('bookingContainers') ? $this->bookingContainers : $this->bookingContainers()->get();

        if ($this->relationLoaded('invoice') ? $this->invoice : $this->invoice()->exists()) {
            return [
                'key'   => 'invoiced',
                'label' => 'فواتير منتهية',
                'badge' => 'badge-dark',
                'icon'  => 'fas fa-check-double',
            ];
        }

        if ($containers->isEmpty()) {
            return [
                'key'   => 'assigned',
                'label' => 'التخصيص',
                'badge' => 'badge-secondary',
                'icon'  => 'fas fa-tasks',
            ];
        }

        if ($containers->every(fn($c) => $c->superagent_unloading_approved == 1)) {
            return [
                'key'   => 'invoiced',
                'label' => 'فواتير منتهية',
                'badge' => 'badge-dark',
                'icon'  => 'fas fa-check-double',
            ];
        }

        if ($containers->contains(fn($c) => $c->superagent_loading_approved == 1 && $c->superagent_unloading_approved == 0)) {
            return [
                'key'   => 'unloading',
                'label' => 'التعتيق',
                'badge' => 'badge-info',
                'icon'  => 'fas fa-dolly',
            ];
        }

        if ($containers->contains(fn($c) => $c->superagent_specification_approved == 1 && $c->is_in_loading == 1 && $c->superagent_loading_approved == 0)) {
            return [
                'key'   => 'loading',
                'label' => 'التحميل',
                'badge' => 'badge-primary',
                'icon'  => 'fas fa-truck-loading',
            ];
        }

        if ($containers->contains(fn($c) => $c->superagent_specification_approved == 1 && $c->is_in_loading == 0 && $c->superagent_loading_approved == 0)) {
            return [
                'key'   => 'waiting',
                'label' => 'الانتظار',
                'badge' => 'badge-warning text-dark',
                'icon'  => 'fas fa-hourglass-half',
            ];
        }

        return [
            'key'   => 'assigned',
            'label' => 'التخصيص',
            'badge' => 'badge-secondary',
            'icon'  => 'fas fa-tasks',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($post) {
            // Set the user_id to the authenticated user's ID
            if (Auth::check()) {
                $post->user_id = Auth::id();
            }
        });
    }
}
