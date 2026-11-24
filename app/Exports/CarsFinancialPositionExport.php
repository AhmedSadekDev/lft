<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CarsFinancialPositionExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(
        protected $carsWithDebts,
        protected string $reportDate,
        protected float $totalDebts
    ) {
    }

    public function collection()
    {
        $data = collect();

        foreach ($this->carsWithDebts as $index => $car) {
            $data->push([
                $index + 1,
                $car['car_number'],
                number_format((float) $car['balance'], 2),
            ]);
        }

        if ($this->carsWithDebts->count() > 0) {
            $data->push([
                '',
                'صافي الإجمالي',
                number_format((float) $this->totalDebts, 2),
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            '#',
            'رقم السيارة',
            'الرصيد المستحق',
        ];
    }

    public function title(): string
    {
        return 'الموقف المالي - السيارات';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 28,
            'C' => 20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->carsWithDebts->count() + 2;

        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DC143C'],
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

        if ($this->carsWithDebts->count() > 0) {
            $sheet->getStyle('A2:C'.($lastRow - 1))->applyFromArray([
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

            $sheet->getStyle('C2:C'.($lastRow - 1))->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'D32F2F'],
                ],
            ]);

            $sheet->getStyle('A'.$lastRow.':C'.$lastRow)->applyFromArray([
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
        }

        return [];
    }
}
