<?php

namespace App\Exports;

use App\Models\Company;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Carbon\Carbon;

class AccountStatementExport implements WithMultipleSheets
{
    protected $company;
    protected $fromDate;
    protected $toDate;
    protected $carriedForwardBalance;
    protected $totalInvoices;
    protected $totalPayments;
    protected $finalBalance;
    protected $invoices;
    protected $payments;
    protected $transactions;

    public function __construct($company, $fromDate, $toDate, $carriedForwardBalance, $totalInvoices, $totalPayments, $finalBalance, $invoices, $payments, $transactions)
    {
        $this->company = $company;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->carriedForwardBalance = $carriedForwardBalance;
        $this->totalInvoices = $totalInvoices;
        $this->totalPayments = $totalPayments;
        $this->finalBalance = $finalBalance;
        $this->invoices = $invoices;
        $this->payments = $payments;
        $this->transactions = $transactions;
    }

    public function sheets(): array
    {
        return [
            new AccountStatementTransactionsSheet($this->company, $this->fromDate, $this->toDate, $this->transactions, $this->finalBalance),
            new AccountStatementSummarySheet($this->company, $this->fromDate, $this->toDate, $this->carriedForwardBalance, $this->totalInvoices, $this->totalPayments, $this->finalBalance),
        ];
    }
}

class AccountStatementTransactionsSheet implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    protected $company;
    protected $fromDate;
    protected $toDate;
    protected $transactions;
    protected $finalBalance;

    public function __construct($company, $fromDate, $toDate, $transactions, $finalBalance)
    {
        $this->company = $company;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->transactions = $transactions;
        $this->finalBalance = $finalBalance;
    }

    public function collection()
    {
        $data = collect();

        // إضافة صف العنوان (بدون headings)
        $data->push([
            $this->company->name,
            '',
            '',
            '',
            'الحساب في الفترة',
            '',
            '',
            '',
            '',
            '',
            '',
            "من {$this->fromDate} الى {$this->toDate}",
            '',
            '',
        ]);

        // إضافة صف فارغ
        $data->push([
            '', '', '', '', '', '', '', '', '', '', '', '', '', ''
        ]);

        // إضافة الحركات (بدون headings لأن headings() ستعتني بهذا)
        foreach ($this->transactions as $transaction) {
            $date = $transaction['date'] instanceof \Carbon\Carbon ? $transaction['date'] : \Carbon\Carbon::parse($transaction['date']);

            $data->push([
                $date->format('Y-m-d H:i'),
                $transaction['previous_debit'] > 0 ? number_format($transaction['previous_debit'], 2) : '',
                $transaction['previous_credit'] > 0 ? number_format($transaction['previous_credit'], 2) : '',
                $transaction['booking_number'] ?: '',
                $transaction['type_label'],
                $transaction['discount'] > 0 ? number_format($transaction['discount'], 2) : '',
                $transaction['tax'] > 0 ? number_format($transaction['tax'], 2) : '',
                $transaction['attachment_statement'] ?: '',
                $transaction['transportation'] > 0 ? number_format($transaction['transportation'], 2) : '',
                $transaction['total'] > 0 ? number_format($transaction['total'], 2) : '',
                $transaction['paid'] > 0 ? number_format($transaction['paid'], 2) : '',
                $transaction['notes'] ?: '',
                $transaction['current_debit'] > 0 ? number_format($transaction['current_debit'], 2) : '',
                $transaction['current_credit'] > 0 ? number_format($transaction['current_credit'], 2) : '',
            ]);
        }

        // إضافة صف الإجمالي
        if ($this->transactions->count() > 0) {
            $totalPreviousDebit = $this->transactions->sum('previous_debit');
            $totalPreviousCredit = $this->transactions->sum('previous_credit');
            $totalCurrentDebit = $this->transactions->sum('current_debit');
            $totalCurrentCredit = $this->transactions->sum('current_credit');

            $data->push([
                '', '', '', '', '', '', '', '', '', '', '', '', '', ''
            ]);

            $data->push([
                "الحساب النهائي يوم {$this->toDate}",
                number_format($totalPreviousDebit, 2),
                number_format($totalPreviousCredit, 2),
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                number_format($totalCurrentDebit, 2),
                number_format($totalCurrentCredit, 2),
            ]);

            $data->push([
                'الرصيد النهائي المستحق',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                number_format(abs($this->finalBalance), 2),
                '',
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'التاريخ',
            'حساب سابق - مدين',
            'حساب سابق - دائن',
            'رقم الطلب',
            'نوع العملية',
            'خصم على الفاتورة',
            'الضريبة',
            'بيان ملحق',
            'فاتورة النقل',
            'القيمة الاجمالية',
            'تم دفع',
            'ملاحظات',
            'مدين',
            'دائن',
        ];
    }

    public function title(): string
    {
        return 'كشف الحساب';
    }
}

class AccountStatementSummarySheet implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    protected $company;
    protected $fromDate;
    protected $toDate;
    protected $carriedForwardBalance;
    protected $totalInvoices;
    protected $totalPayments;
    protected $finalBalance;

    public function __construct($company, $fromDate, $toDate, $carriedForwardBalance, $totalInvoices, $totalPayments, $finalBalance)
    {
        $this->company = $company;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->carriedForwardBalance = $carriedForwardBalance;
        $this->totalInvoices = $totalInvoices;
        $this->totalPayments = $totalPayments;
        $this->finalBalance = $finalBalance;
    }

    public function collection()
    {
        return collect([
            ['معلومات الشركة', ''],
            ['الاسم', $this->company->name],
            ['البريد الإلكتروني', $this->company->email ?? '-'],
            ['الهاتف', $this->company->phone ?? '-'],
            ['', ''],
            ['ملخص الحساب', ''],
            ['من تاريخ', $this->fromDate],
            ['إلى تاريخ', $this->toDate],
            ['الرصيد المرحّل', number_format($this->carriedForwardBalance, 2)],
            ['إجمالي الفواتير', number_format($this->totalInvoices, 2)],
            ['إجمالي السداد', number_format($this->totalPayments, 2)],
            ['الرصيد النهائي المستحق', number_format($this->finalBalance, 2)],
        ]);
    }

    public function headings(): array
    {
        return ['الوصف', 'القيمة'];
    }

    public function title(): string
    {
        return 'ملخص الحساب';
    }
}

class AccountStatementInvoicesSheet implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    protected $invoices;

    public function __construct($invoices)
    {
        $this->invoices = $invoices;
    }

    public function collection()
    {
        // التأكد من أن $invoices هي collection
        if (is_array($this->invoices)) {
            $invoicesCollection = collect($this->invoices);
        } elseif ($this->invoices instanceof Collection) {
            $invoicesCollection = $this->invoices;
        } else {
            $invoicesCollection = collect([]);
        }

        if ($invoicesCollection->isEmpty()) {
            return collect([
                [
                    'invoice_number' => 'لا توجد فواتير',
                    'booking_number' => '',
                    'date' => '',
                    'total' => '',
                    'paid' => '',
                    'remaining' => '',
                ]
            ]);
        }

        return $invoicesCollection->map(function ($invoice) {
            // التعامل مع array
            $invoiceNumber = $invoice['invoice_number'] ?? '-';
            $bookingNumber = $invoice['booking_number'] ?? '-';
            $date = $invoice['date'] ?? now();
            $total = $invoice['total'] ?? 0;
            $paid = $invoice['paid'] ?? 0;
            $remaining = $invoice['remaining'] ?? 0;

            // تحويل التاريخ إذا كان Carbon instance
            if ($date instanceof \Carbon\Carbon) {
                $dateFormatted = $date->format('Y-m-d');
            } elseif (is_string($date)) {
                $dateFormatted = $date;
            } else {
                $dateFormatted = now()->format('Y-m-d');
            }

            return [
                'invoice_number' => $invoiceNumber,
                'booking_number' => $bookingNumber,
                'date' => $dateFormatted,
                'total' => is_numeric($total) ? number_format($total, 2) : $total,
                'paid' => is_numeric($paid) ? number_format($paid, 2) : $paid,
                'remaining' => is_numeric($remaining) ? number_format($remaining, 2) : $remaining,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'رقم الفاتورة',
            'رقم الشحنة',
            'التاريخ',
            'إجمالي الفاتورة',
            'المدفوع',
            'المتبقي',
        ];
    }

    public function title(): string
    {
        return 'الفواتير';
    }
}

class AccountStatementPaymentsSheet implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    protected $payments;

    public function __construct($payments)
    {
        $this->payments = $payments;
    }

    public function collection()
    {
        return $this->payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'invoice_number' => $payment->invoice->invoice_number ?? '-',
                'value' => number_format($payment->value, 2),
                'date' => $payment->created_at->format('Y-m-d'),
                'has_image' => $payment->image ? 'نعم' : 'لا',
            ];
        });
    }

    public function headings(): array
    {
        return [
            '#',
            'رقم الفاتورة',
            'المبلغ',
            'التاريخ',
            'يوجد صورة',
        ];
    }

    public function title(): string
    {
        return 'السداد';
    }
}
