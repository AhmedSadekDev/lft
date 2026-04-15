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
        $parts = explode('-', $max_invoice_number);

        return intval(end($parts) ?: '0');
    }

    /**
     * تنسيق رقم الفاتورة: YYYY-MM-####.
     * يبدأ التسلسل من 5001 لكل شهر، ويزيد حسب آخر فاتورة بنفس الشهر.
     */
    public static function getNextInvoiceNumberForCompany(int $company_id): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "{$year}-{$month}-";

        $lastNumber = self::where('invoice_number', 'like', $prefix . '%')
            ->orderByRaw("CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED) DESC")
            ->value('invoice_number');

        $nextSequence = 5001;
        if (!empty($lastNumber) && preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $lastNumber, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        return $prefix . $nextSequence;
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
