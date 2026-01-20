<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

class FinancialPositionExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle, WithStyles, WithColumnWidths
{
    protected $companiesWithDebts;
    protected $reportDate;
    protected $totalDebts;

    public function __construct($companiesWithDebts, $reportDate, $totalDebts)
    {
        $this->companiesWithDebts = $companiesWithDebts;
        $this->reportDate = $reportDate;
        $this->totalDebts = $totalDebts;
    }

    public function collection()
    {
        $data = collect();

        foreach ($this->companiesWithDebts as $index => $company) {
            $data->push([
                $index + 1,
                $company['name'],
                $company['email'],
                $company['phone'],
                number_format($company['total_invoices'], 2),
                number_format($company['total_payments'], 2),
                number_format($company['balance'], 2),
            ]);
        }

        // إضافة صف الإجمالي
        if ($this->companiesWithDebts->count() > 0) {
            $data->push([
                '',
                'الإجمالي',
                '',
                '',
                number_format($this->companiesWithDebts->sum('total_invoices'), 2),
                number_format($this->companiesWithDebts->sum('total_payments'), 2),
                number_format($this->totalDebts, 2),
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            '#',
            'اسم الشركة',
            'البريد الإلكتروني',
            'الهاتف',
            'إجمالي الفواتير',
            'إجمالي المدفوعات',
            'الرصيد المستحق',
        ];
    }

    public function title(): string
    {
        return 'الشركات المدينة';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 30,
            'C' => 25,
            'D' => 15,
            'E' => 18,
            'F' => 18,
            'G' => 18,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->companiesWithDebts->count() + 2; // +2 للرأس والصف الإجمالي

        // تنسيق رأس الجدول
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4CAF50'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // تنسيق البيانات
        if ($this->companiesWithDebts->count() > 0) {
            $sheet->getStyle('A2:G' . ($lastRow - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // تنسيق عمود الرصيد المستحق (باللون الأحمر)
            $sheet->getStyle('G2:G' . ($lastRow - 1))->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'D32F2F'],
                ],
            ]);

            // تنسيق صف الإجمالي
            $sheet->getStyle('A' . $lastRow . ':G' . $lastRow)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E3F2FD'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // تنسيق عمود الرصيد المستحق في صف الإجمالي
            $sheet->getStyle('G' . $lastRow)->applyFromArray([
                'font' => [
                    'color' => ['rgb' => 'D32F2F'],
                ],
            ]);
        }

        // تنسيق عمود اسم الشركة (محاذاة لليمين)
        $sheet->getStyle('B2:B' . ($lastRow > 1 ? $lastRow : 2))->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ],
        ]);

        return [];
    }
}
