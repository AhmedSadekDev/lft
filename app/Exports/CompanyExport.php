<?php

namespace App\Exports;
use Illuminate\Support\Collection;

use App\Models\Company;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompanyExport implements FromCollection, WithHeadings, ShouldAutoSize
{
     private $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $company = Company::with('bookings.invoice.invoicePayments')->findOrFail($this->id);

        // Initialize a collection to hold the results
        $paymentDetails = new Collection();

        foreach ($company->bookings as $booking) {
            $invoice = $booking->invoice;

            if ($invoice) {
                $totalInvoiceAmount = $this->calculateTotalInvoiceAmount($invoice);
                $amountPaid = $invoice->invoicePayments->sum('value');

                foreach ($invoice->invoicePayments as $payment) {
                    $remainingAmount = $totalInvoiceAmount - $amountPaid;

                    $paymentDetails->push([
                        'invoice_id' => $invoice->id,
                        'amount' => $totalInvoiceAmount,
                        'paid' => $amountPaid,
                        'remaining' => $remainingAmount,
                        'payment' => $payment->value,
                        'payment_date' => $payment->created_at->format('Y-m-d'),
                    ]);
                }
            }
        }

        return $paymentDetails;
    }

    /**
     * Calculate the total invoice amount.
     *
     * @param $invoice
     * @return float
     */
    private function calculateTotalInvoiceAmount($invoice)
    {
        $invoiceTotalBeforeTax = $invoice->invoice_total_before_tax;
        $vatValue = $invoice->value_added_tax_amount;
        $saleValue = $invoice->sales_tax_amount;
        $discountValue = $invoice->discount_amount;

        $taxedServicesTotal = $invoice->taxed_services_total_before_vat ?? 0;
        $untaxedServicesTotal = $invoice->untaxed_services_total_before_vat ?? 0;
        $transportationTotal = $invoice->transportation_total_before_vat ?? 0;

        return $invoiceTotalBeforeTax + $taxedServicesTotal + $untaxedServicesTotal + $vatValue - $saleValue - $discountValue;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'رقم الفاتورة',
            'المبلغ الإجمالي',
            'المبلغ المدفوع',
            'المبلغ المتبقي',
            'مبلغ الدفع',
            'تاريخ الدفع',
        ];

    }
}
