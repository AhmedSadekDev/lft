<?php

namespace App\Exports;

use App\Models\Car;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Carbon\Carbon;

class CarStatementExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    protected $car;
    protected $fromDate;
    protected $toDate;
    protected $carriedForwardBalance;
    protected $transactions;
    protected $totalValue;
    protected $totalCustody;
    protected $totalPayments;
    protected $finalBalance;

    public function __construct($car, $fromDate, $toDate, $carriedForwardBalance, $transactions, $totalValue, $totalCustody, $totalPayments, $finalBalance)
    {
        $this->car = $car;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->carriedForwardBalance = $carriedForwardBalance;
        $this->transactions = $transactions;
        $this->totalValue = $totalValue;
        $this->totalCustody = $totalCustody;
        $this->totalPayments = $totalPayments;
        $this->finalBalance = $finalBalance;
    }

    public function collection()
    {
        $data = collect();

        // إضافة صف العنوان
        $data->push([
            $this->car->car_number,
            '',
            '',
            'الحساب في الفترة',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            "من {$this->fromDate} الى {$this->toDate}",
        ]);

        // إضافة صف فارغ
        $data->push([
            '', '', '', '', '', '', '', '', '', '', '', '', '', ''
        ]);

        // إضافة الحركات
        foreach ($this->transactions as $transaction) {
            $date = $transaction['date'] instanceof \Carbon\Carbon ? $transaction['date'] : \Carbon\Carbon::parse($transaction['date']);

            $data->push([
                $date->format('Y-m-d H:i'),
                $transaction['previous_balance'] > 0 ? number_format($transaction['previous_balance'], 2) : '',
                $transaction['service'] ?: '',
                $transaction['description'] ?: '',
                $transaction['container_no'] ?: '',
                $transaction['departure'] ?: '',
                $transaction['destination'] ?: '',
                $transaction['aging'] ?: '',
                $transaction['value'] > 0 ? number_format($transaction['value'], 2) : '',
                $transaction['custody'] > 0 ? number_format($transaction['custody'], 2) : '',
                $transaction['total1'] > 0 ? number_format($transaction['total1'], 2) : '',
                $transaction['total2'] > 0 ? number_format($transaction['total2'], 2) : '',
                $transaction['debit_credit'],
                number_format($transaction['running_balance'], 2),
            ]);
        }

        // إضافة صف الإجمالي
        if ($this->transactions->count() > 0) {
            $totalPreviousBalance = $this->transactions->sum('previous_balance');
            $totalValue = $this->transactions->sum('value');
            $totalCustody = $this->transactions->sum('custody');
            $total1 = $this->transactions->sum('total1');
            $total2 = $this->transactions->sum('total2');

            $data->push([
                '', '', '', '', '', '', '', '', '', '', '', '', '', ''
            ]);

            $data->push([
                "الحساب النهائي يوم {$this->toDate}",
                number_format($totalPreviousBalance, 2),
                '',
                '',
                '',
                '',
                '',
                '',
                number_format($totalValue, 2),
                number_format($totalCustody, 2),
                number_format($total1, 2),
                number_format($total2, 2),
                '',
                '',
            ]);

            $data->push([
                'الرصيد النهائي المستحق',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                number_format(abs($this->finalBalance), 2),
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'التاريخ',
            'حساب سابق',
            'الخدمة',
            'الوصف',
            'رقم الحاوية',
            'خروج',
            'الوجهة',
            'تعتيق',
            'القيمة',
            'العهدة',
            'الإجمالي',
            'الإجمالي',
            'مدين أو دائن',
            'اجمالي النقلة + حساب سابق',
        ];
    }

    public function title(): string
    {
        return 'كشف حساب السيارة';
    }
}
