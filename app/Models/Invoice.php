<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'booking_id',

        'booking_json',
        'transportation_json',
        'taxed_services_json',
        'untaxed_services_json',

        'transportation_total_before_vat',
        'taxed_services_total_before_vat',
        'untaxed_services_total_before_vat',

        'value_added_tax',
        'sales_tax',
        'discount',
    ];

    protected $casts = [
        'booking_json' => 'json',
        'transportation_json' => 'json',
        'taxed_services_json' => 'json',
        'untaxed_services_json' => 'json',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function invoicePayments()
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function getSavedTransportationAttribute()
    {
        return BookingContainer::hydrate([$this->transportation_json])->first();
    }
    public function getSavedTaxedServicesAttribute()
    {
        return BookingService::hydrate([$this->taxed_services_json])->first();
    }
    public function getSavedUntaxedServicesAttribute()
    {
        return BookingService::hydrate([$this->untaxed_services_json])->first();
    }
    public function getSavedBookingAttribute()
    {
        return Booking::hydrate([$this->booking_json])->first();
    }

    public static function getMaxCompanyInvoiceNumber($company_id)
    {
        $max_invoice_number = self::where('invoice_number', 'like', '%-' . invoiceNumberTrim($company_id) . '-%')
            ->select(DB::raw('MAX(invoice_number) as max_invoice_number'))
            ->first()
            ->max_invoice_number ?? '0000-000-000';
        return intval(explode('-', $max_invoice_number)[2] ?? '000');
    }

    /**
     * Get next invoice number for company (format: YYYY-MM-DD-XXX-NNN, sequence resets each day).
     */
    public static function getNextInvoiceNumberForCompany(int $company_id): string
    {
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $companyPart = invoiceNumberTrim($company_id);

        $count = self::whereHas('booking', fn ($q) => $q->where('company_id', $company_id))
            ->whereDate('created_at', date('Y-m-d'))
            ->count();

        $sequence = $count + 1;
        return "{$year}-{$month}-{$day}-{$companyPart}-" . invoiceNumberTrim($sequence);
    }


   public function getInvoiceTotalBeforeTaxAttribute()
    {
        // حساب مجموع الخدمات الخاضعة للضريبة غير الإيصالات
        $nonReceiptTaxedServicesTotal = 0;
        if ($this->booking) {
            $taxedServices = $this->booking->getTaxedServices()->get();
            foreach ($taxedServices as $service) {
                $fullName = $service->full_name ?? '';
                // استبعاد الإيصالات من الحساب
                if (stripos($fullName, 'ايصالات') === false && stripos($fullName, 'receipt') === false) {
                    $nonReceiptTaxedServicesTotal += $service->price ?? 0;
                }
            }
        }

        return ceil(
            $this->transportation_total_before_vat
                + $nonReceiptTaxedServicesTotal
        );
    }

    public function getValueAddedTaxAmountAttribute()
    {
        return ceil(
            $this->invoice_total_before_tax
                * ($this->value_added_tax / 100)
        );
    }

    public function getSalesTaxAmountAttribute()
    {
        return ceil(
            $this->invoice_total_before_tax
                * ($this->sales_tax / 100)
        );
    }

    public function getInvoiceTotalAfterTaxAttribute()
    {
        return ceil(
            $this->invoice_total_before_tax
                + $this->value_added_tax_amount
        );
    }

    public function getDiscountAmountAttribute()
    {
        // Discount amount is fixed cost, so it's directly the discount value
        return $this->discount;
    }

    public function getInvoiceTotalAfterDiscountAttribute()
    {
        return ceil(
            $this->invoice_total_after_tax
                - $this->discount_amount
        );
    }

}
