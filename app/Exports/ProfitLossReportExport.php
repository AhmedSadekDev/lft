<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Carbon\Carbon;

class ProfitLossReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    protected $reportData;
    protected $fromDate;
    protected $toDate;

    public function __construct($reportData, $fromDate, $toDate)
    {
        $this->reportData = $reportData;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function collection()
    {
        $data = collect();

        // إضافة صف العنوان
        $data->push([
            'تقرير الأرباح والخسائر',
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
            '', '', '', '', '', '', '', '', ''
        ]);

        // إضافة البيانات
        foreach ($this->reportData as $index => $row) {
            $date = $row['invoice_date'] instanceof \Carbon\Carbon ? $row['invoice_date'] : \Carbon\Carbon::parse($row['invoice_date']);

            $data->push([
                $index + 1,
                $row['booking_number'],
                $row['invoice_number'],
                $row['company_name'],
                $date->format('Y-m-d'),
                is_array($row['expenses_description']) ? implode('، ', $row['expenses_description']) : ($row['expenses_description'] ?? ''),
                number_format($row['total_cost'], 2),
                number_format($row['invoice_total'], 2),
                number_format($row['profit_loss'], 2),
            ]);
        }

        // إضافة صف الإجمالي
        if ($this->reportData->count() > 0) {
            $totalCost = $this->reportData->sum('total_cost');
            $totalRevenue = $this->reportData->sum('invoice_total');
            $totalProfitLoss = $this->reportData->sum('profit_loss');

            $data->push([
                '', '', '', '', '', ''
            ]);

            $data->push([
                '',
                '',
                '',
                '',
                '',
                '',
                'الإجمالي',
                number_format($totalCost, 2),
                number_format($totalRevenue, 2),
                number_format($totalProfitLoss, 2),
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            '#',
            'رقم الطلب',
            'رقم الفاتورة',
            'اسم الشركة',
            'تاريخ الفاتورة',
            'وصف المصروفات',
            'التكلفة الفعلية',
            'سعر الفاتورة',
            'الربح/الخسارة',
        ];
    }

    public function title(): string
    {
        return 'تقرير الأرباح والخسائر';
    }
}
