<?php

namespace App\Exports\EInvoice;

class TransactionStatementExport extends BaseEInvoiceExport
{
    public function title(): string
    {
        return 'بيان تعاملات';
    }

    public function headings(): array
    {
        return [
            'المعرف الإلكتروني',
            'المعرف الداخلي',
            'رقم الحجز',
            'نوع المستند',
            'الحالة',
            'تاريخ الإصدار',
            'البائع',
            'المشتري',
            'الإجمالي',
        ];
    }

    public function array(): array
    {
        return $this->mappedRows->map(function (array $row) {
            return [
                $row['electronic_id'] ?? '',
                $row['internal_id'] ?? '',
                $row['booking_number'] ?? '',
                $row['document_type'] ?? '',
                $row['status'] ?? '',
                $row['date_issued'] ?? '',
                trim(($row['seller_name'] ?? '').' / '.($row['seller_tax'] ?? '')),
                trim(($row['buyer_name'] ?? '').' / '.($row['buyer_tax'] ?? '')),
                $row['total_amount'] ?? 0,
            ];
        })->values()->all();
    }
}
