<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * الجزء الأوسط في رقم الفاتورة (ثلاثة أرقام): private_company_id إن وُجد، وإلا معرف الشركة.
     */
    protected static function companyInvoiceSerialSegment(int $company_id): string
    {
        $company = Company::query()->find($company_id);
        if ($company !== null) {
            $key = $company->private_company_id ?? $company->id;

            return invoiceNumberTrim((int) $key);
        }

        return invoiceNumberTrim($company_id);
    }

    /**
     * أعلى تسلسل فاتورة (الجزء الأخير) لنفس السنة YYYY والجزء الأوسط XXX في تنسيق YYYY-XXX-###.
     */
    protected static function maxInvoiceSequenceForSegment(string $companyTrim, string $year): int
    {
        $pattern = '^' . $year . '-' . $companyTrim . '-[0-9]{3}$';

        $lastNumber = self::whereRaw('invoice_number REGEXP ?', [$pattern])
            ->orderByRaw('CAST(SUBSTRING_INDEX(invoice_number, "-", -1) AS UNSIGNED) DESC')
            ->value('invoice_number');

        if (!empty($lastNumber) && preg_match('/^' . preg_quote($year, '/') . '-' . preg_quote($companyTrim, '/') . '-(\d{3})$/', $lastNumber, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    /**
     * أعلى تسلسل فاتورة لهذه الشركة في السنة الحالية (الجزء الأخير من YYYY-XXX-###).
     * لا يطابق الفواتير القديمة ذات تنسيق آخر.
     */
    public static function getMaxCompanyInvoiceNumber($company_id): int
    {
        return self::maxInvoiceSequenceForSegment(
            self::companyInvoiceSerialSegment($company_id),
            date('Y')
        );
    }

    /**
     * تنسيق رقم الفاتورة: YYYY-XXX-### (سنة، ثم معرف الشركة بثلاثة أرقام، ثم مسلسل الفاتورة بثلاثة أرقام لكل شركة في السنة).
     * مثال: 2026-006-001، 2026-006-002.
     */
    public static function getNextInvoiceNumberForCompany(int $company_id): string
    {
        $year = date('Y');
        $companyTrim = self::companyInvoiceSerialSegment($company_id);
        $nextSequence = self::maxInvoiceSequenceForSegment($companyTrim, $year) + 1;

        return $year . '-' . $companyTrim . '-' . str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);
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
