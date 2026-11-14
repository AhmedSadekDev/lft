<?php

namespace App\Exports\EInvoice;

class Form41Export extends BaseEInvoiceExport
{
    public function title(): string
    {
        return 'نموذج 41';
    }

    public function headings(): array
    {
        return [
            'مسلسل',
            'تاريخ التعامل',
            'رقم التسجيل الضريبي للطرف الآخر',
            'اسم الطرف الآخر',
            'رقم الفاتورة',
            'قيمة التعامل',
            'الضريبة',
            'نوع المستند',
            'UUID',
        ];
    }

    public function array(): array
    {
        $i = 1;

        return $this->mappedRows->map(function (array $row) use (&$i) {
            return [
                $i++,
                substr((string) ($row['date_issued'] ?? ''), 0, 10),
                $row['buyer_tax'] ?: ($row['seller_tax'] ?? ''),
                $row['buyer_name'] ?: ($row['seller_name'] ?? ''),
                $row['internal_id'] ?? '',
                $row['net_amount'] ?? 0,
                $row['tax_total'] ?? 0,
                $row['document_type'] ?? '',
                $row['electronic_id'] ?? '',
            ];
        })->values()->all();
    }
}
