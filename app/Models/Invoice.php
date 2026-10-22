<?php

namespace App\Models;

use App\Support\InvoiceCurrency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'currency',
        'usd_amount',
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

    protected $attributes = [
        'currency' => InvoiceCurrency::EGP,
        'usd_amount' => 0,
    ];

    protected $casts = [
        'booking_json' => 'json',
        'transportation_json' => 'json',
        'taxed_services_json' => 'json',
        'untaxed_services_json' => 'json',
        'usd_amount' => 'float',
    ];

    public function isUsd(): bool
    {
        return InvoiceCurrency::isUsd($this->currency ?? InvoiceCurrency::EGP);
    }

    public function currencySymbol(): string
    {
        return InvoiceCurrency::symbol($this->currency ?? InvoiceCurrency::EGP);
    }

    public function currencyLabel(string $locale = 'ar'): string
    {
        return InvoiceCurrency::label($this->currency ?? InvoiceCurrency::EGP, $locale);
    }

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
     * أعلى مسلسل فاتورة (الجزء الأخير ###) في السنة — عبر كل الشركات/الأكواد.
     * يضم التنسيق القديم YYYY-XXX-### والتنسيق الجديد YYYY-XXXXX-###.
     */
    protected static function maxInvoiceSequenceForYear(string $year): int
    {
        $max = 0;
        self::where('invoice_number', 'like', $year . '-%')
            ->select(['id', 'invoice_number'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$max) {
                foreach ($rows as $row) {
                    if (preg_match('/-(\d{3})$/', (string) $row->invoice_number, $matches)) {
                        $max = max($max, (int) $matches[1]);
                    }
                }
            });

        return $max;
    }

    /**
     * تنسيق رقم الفاتورة: YYYY-{public_code}-###  
     * السنة + الرقم الداخلي العشوائي للحجز (5 أرقام) + مسلسل عام حسب ترتيب إنشاء الفواتير في السنة.
     * مثال: 2026-48291-015
     */
    public static function getNextInvoiceNumberForBooking(Booking $booking): string
    {
        $year = date('Y');
        $publicCode = $booking->ensurePublicCode();
        $nextSequence = self::maxInvoiceSequenceForYear($year) + 1;

        return $year . '-' . $publicCode . '-' . str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * @deprecated Use getNextInvoiceNumberForBooking()
     */
    public static function getNextInvoiceNumberForCompany(int $company_id): string
    {
        $booking = Booking::query()->where('company_id', $company_id)->latest('id')->first();
        if ($booking) {
            return self::getNextInvoiceNumberForBooking($booking);
        }

        $year = date('Y');
        $nextSequence = self::maxInvoiceSequenceForYear($year) + 1;

        return $year . '-00000-' . str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * يخصص رقم فاتورة فريد داخل المعاملة الحالية للحجز.
     */
    public static function allocateUniqueInvoiceNumber(Booking|int $bookingOrCompanyId, ?string $preferred = null): string
    {
        $booking = $bookingOrCompanyId instanceof Booking
            ? $bookingOrCompanyId
            : Booking::query()->where('company_id', $bookingOrCompanyId)->latest('id')->first();

        abort_unless($booking, 422, 'الحجز مطلوب لتوليد رقم الفاتورة');

        $year = date('Y');
        $publicCode = $booking->ensurePublicCode();

        // قفل فواتير السنة لتفادي تزامن مسلسلين متطابقين
        self::where('invoice_number', 'like', $year . '-%')
            ->lockForUpdate()
            ->get(['id', 'invoice_number']);

        $preferred = is_string($preferred) ? trim($preferred) : '';
        if ($preferred !== '' && ! self::where('invoice_number', $preferred)->exists()) {
            return $preferred;
        }

        $sequence = self::maxInvoiceSequenceForYear($year) + 1;
        for ($i = 0; $i < 1000; $i++, $sequence++) {
            $candidate = $year . '-' . $publicCode . '-' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            if (! self::where('invoice_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException('تعذر توليد رقم فاتورة جديد');
    }

    public function getInvoiceTotalBeforeTaxAttribute()
    {
        $invoiceCurrency = InvoiceCurrency::normalize($this->currency ?? InvoiceCurrency::EGP);

        // حساب مجموع الخدمات الخاضعة للضريبة غير الإيصالات
        $nonReceiptTaxedServicesTotal = 0;
        if ($this->booking) {
            $taxedServices = $this->booking->getTaxedServices()->get();
            foreach ($taxedServices as $service) {
                $fullName = $service->full_name ?? '';
                // استبعاد الإيصالات من الحساب
                if (stripos($fullName, 'ايصالات') === false && stripos($fullName, 'receipt') === false) {
                    $nonReceiptTaxedServicesTotal += InvoiceCurrency::invoiceLineAmount($service, $invoiceCurrency);
                }
            }
        }

        $transportation = (float) $this->transportation_total_before_vat;
        // Pure USD invoices: transportation snapshot is already in USD (entered on containers).

        return round($transportation + (float) $nonReceiptTaxedServicesTotal, 2);
    }

    public function getValueAddedTaxAmountAttribute()
    {
        // round أولاً لتجنب خطأ الفاصلة العائمة مع ceil (مثال: 191000*0.14 => 26740.000000000004)
        return round(
            (float) $this->invoice_total_before_tax * ((float) $this->value_added_tax / 100),
            2
        );
    }

    public function getSalesTaxAmountAttribute()
    {
        return round(
            (float) $this->invoice_total_before_tax * ((float) $this->sales_tax / 100),
            2
        );
    }

    public function getInvoiceTotalAfterTaxAttribute()
    {
        return round(
            (float) $this->invoice_total_before_tax + (float) $this->value_added_tax_amount,
            2
        );
    }

    public function getDiscountAmountAttribute()
    {
        // Discount amount is fixed cost, so it's directly the discount value
        return $this->discount;
    }

    public function getInvoiceTotalAfterDiscountAttribute()
    {
        return round(
            (float) $this->invoice_total_after_tax - (float) $this->discount_amount,
            2
        );
    }

}
