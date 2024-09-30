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


    

    public function expenses(){
        return $this->hasMany(AgentExpense::class);
    }



    public function company()
    {
        return $this->belongsTo(Company::class);
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

        $y = $this->expenses()->whereHas('service', function($query) {
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