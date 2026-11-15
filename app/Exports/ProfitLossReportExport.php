<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProfitLossReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    protected $reportData;
    protected $fromDate;
    protected $toDate;
    protected $summary;
    protected $companyAdjustments;

    public function __construct($reportData, $fromDate, $toDate, array $summary = [], $companyAdjustments = null)
    {
        $this->reportData = $reportData;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->summary = $summary;
        $this->companyAdjustments = collect($companyAdjustments ?? []);
    }

    public function collection()
    {
        $data = collect();

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
        $data->push(['', '', '', '', '', '', '', '', '']);

        $data->push(['الملخص', '', '', '', '', '', '', '', '']);
        $data->push([
            'إجمالي الفواتير',
            ($this->summary['invoice_count'] ?? 0) . ' فاتورة',
            '',
            'قيمة الفواتير',
            number_format((float) ($this->summary['invoice_total'] ?? 0), 2),
            'التكلفة',
            number_format((float) ($this->summary['invoice_cost'] ?? 0), 2),
            'صافي الربح',
            number_format((float) ($this->summary['invoice_net_profit'] ?? 0), 2),
        ]);
        $data->push([
            'إشعارات الخصم',
            number_format((float) ($this->summary['discount_notices_total'] ?? 0), 2),
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ]);
        $data->push([
            'إشعارات الإضافة / المدين',
            number_format((float) ($this->summary['addition_notices_total'] ?? 0), 2),
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ]);
        $data->push(['', '', '', '', '', '', '', '', '']);
        $data->push(['تفاصيل الفواتير', '', '', '', '', '', '', '', '']);

        foreach ($this->reportData as $index => $row) {
            $date = $row['invoice_date'] instanceof \Carbon\Carbon
                ? $row['invoice_date']
                : \Carbon\Carbon::parse($row['invoice_date']);

            $data->push([
                $index + 1,
                $row['booking_number'],
                $row['invoice_number'],
                $row['company_name'],
                $date->format('Y-m-d'),
                is_array($row['expenses_description'] ?? null)
                    ? implode('، ', $row['expenses_description'])
                    : ($row['expenses_description'] ?? ''),
                number_format($row['total_cost'], 2),
                number_format($row['invoice_total'], 2),
                number_format($row['profit_loss'], 2),
            ]);
        }

        if ($this->companyAdjustments->count() > 0) {
            $data->push(['', '', '', '', '', '', '', '', '']);
            $data->push(['إشعارات الشركة', '', '', '', '', '', '', '', '']);
            foreach ($this->companyAdjustments as $adj) {
                $data->push([
                    $adj->note_number ?: ('ADJ-' . $adj->id),
                    $adj->typeLabel(),
                    $adj->company->name ?? '',
                    optional($adj->created_at)->format('Y-m-d'),
                    $adj->reason,
                    number_format(abs((float) $adj->amount), 2),
                    $adj->currency,
                    '',
                    '',
                ]);
            }
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
