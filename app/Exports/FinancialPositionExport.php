<?php

namespace App\Exports;

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
                number_format($company['balance'], 2),
            ]);
        }

        if ($this->companiesWithDebts->count() > 0) {
            $data->push([
                '',
                'الإجمالي',
                '',
                '',
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
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->companiesWithDebts->count() + 2;

        $sheet->getStyle('A1:E1')->applyFromArray([
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

        if ($this->companiesWithDebts->count() > 0) {
            $sheet->getStyle('A2:E' . ($lastRow - 1))->applyFromArray([
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

            $sheet->getStyle('E2:E' . ($lastRow - 1))->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'D32F2F'],
                ],
            ]);

            $sheet->getStyle('A' . $lastRow . ':E' . $lastRow)->applyFromArray([
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

            $sheet->getStyle('E' . $lastRow)->applyFromArray([
                'font' => [
                    'color' => ['rgb' => 'D32F2F'],
                ],
            ]);
        }

        $sheet->getStyle('B2:B' . ($lastRow > 1 ? $lastRow : 2))->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ],
        ]);

        return [];
    }
}
