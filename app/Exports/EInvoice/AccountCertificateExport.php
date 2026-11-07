<?php

namespace App\Exports\EInvoice;

class AccountCertificateExport extends BaseEInvoiceExport
{
    public function title(): string
    {
        return 'كشف حساب';
    }

    public function headings(): array
    {
        return [
            'الشركة',
            'الرقم الضريبي',
            'رقم الحجز',
            'المعرف الداخلي',
            'UUID',
            'تاريخ الإصدار',
            'الحالة',
            'صافي',
            'ضريبة',
            'إجمالي',
        ];
    }

    public function array(): array
    {
        return $this->mappedRows->map(function (array $row) {
            return [
                $row['company_name'] ?? ($row['buyer_name'] ?? ''),
                $row['company_tax'] ?? ($row['buyer_tax'] ?? ''),
                $row['booking_number'] ?? '',
                $row['internal_id'] ?? '',
                $row['electronic_id'] ?? '',
                $row['date_issued'] ?? '',
                $row['status'] ?? '',
                $row['net_amount'] ?? 0,
                $row['tax_total'] ?? 0,
                $row['total_amount'] ?? 0,
            ];
        })->values()->all();
    }
}
