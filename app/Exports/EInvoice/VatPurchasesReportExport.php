<?php

namespace App\Exports\EInvoice;

class VatPurchasesReportExport extends BaseEInvoiceExport
{
    public function title(): string
    {
        return 'مشتريات قيمة مضافة';
    }

    public function headings(): array
    {
        return [
            'تاريخ الإصدار',
            'المعرف الداخلي',
            'المعرف الإلكتروني',
            'البائع',
            'الرقم الضريبي للبائع',
            'صافي المبلغ',
            'الضريبة',
            'الإجمالي',
            'الحالة',
        ];
    }

    public function array(): array
    {
        return $this->mappedRows->map(function (array $row) {
            return [
                $row['date_issued'] ?? '',
                $row['internal_id'] ?? '',
                $row['electronic_id'] ?? '',
                $row['seller_name'] ?? '',
                $row['seller_tax'] ?? '',
                $row['net_amount'] ?? 0,
                $row['tax_total'] ?? 0,
                $row['total_amount'] ?? 0,
                $row['status'] ?? '',
            ];
        })->values()->all();
    }
}
