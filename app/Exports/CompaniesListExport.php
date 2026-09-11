<?php

namespace App\Exports;

use App\Models\Company;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CompaniesListExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * Search term (same as index filter)
     */
    protected ?string $search;

    public function __construct(?string $search = null)
    {
        $this->search = $search;
    }

    /**
     * Return companies collection with needed relations preloaded.
     */
    public function collection()
    {
        $query = Company::with(['bookings.invoice.invoicePayments', 'companyInvoices']);

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('tax_no', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('id', 'asc')->get();
    }

    /**
     * Map each company row.
     */
    public function map($company): array
    {
        // حساب الرصيد المتبقي كما في الـ view
        $totalInvoices = 0;
        $totalPayments = 0;

        foreach ($company->bookings as $booking) {
            if ($booking->invoice) {
                $invoice = $booking->invoice;
                $totalInvoices += $invoice->invoice_total_after_discount ?? 0;
                $totalPayments += $invoice->invoicePayments->sum('value') ?? 0;
            }
        }

        $remainingBalance = $totalInvoices - $totalPayments;
        $balanceStatus = $remainingBalance > 0
            ? 'مستحق'
            : ($remainingBalance < 0 ? 'رصيد زائد' : 'مسدد');

        $lastInvoice = $company->companyInvoices()->latest()->first();
        $lastInvoiceDate = $lastInvoice && $lastInvoice->created_at
            ? $lastInvoice->created_at->format('Y-m-d')
            : null;

        return [
            $company->id,
            $company->name,
            $company->address,
            $company->email,
            $company->phone,
            $company->tax_no,
            $company->taxed_invoice,
            $company->bill_type == 1 ? __('admin.bill_type_invoice') : __('admin.bill_type_statement'),
            $lastInvoiceDate,
            // أرقام صافية بدون نص
            $remainingBalance,
            // وصف الرصيد كنص منفصل
            $balanceStatus,
        ];
    }

    /**
     * Excel headings.
     */
    public function headings(): array
    {
        return [
            '#',
            'اسم الشركة',
            'العنوان',
            'البريد الإلكتروني',
            'الهاتف',
            'الرقم الضريبي',
            'الحالة الضريبية',
            'نوع الفاتورة',
            'آخر فاتورة',
            'الرصيد المتبقي',
            'وصف الرصيد',
        ];
    }
}

