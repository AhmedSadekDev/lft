<?php

namespace App\Exports;

use App\Models\DeliveryPolicy;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ShipmentExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    protected $from, $to, $id;

    public function __construct($from = null, $to = null, $id)
    {
        $this->from = $from;
        $this->to = $to;
        $this->id = $id;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = DeliveryPolicy::where('car_id', $this->id)->with(['car', 'booking_containers.departure', 'booking_containers.loading', 'booking_containers.aging', 'money_transfer', 'extraExpenses', 'payingCars']);

        // تطبيق الفلترة حسب التواريخ
        if ($this->from && $this->to) {
            $query->whereBetween('created_at', [$this->from, $this->to]);
        } elseif ($this->from) {
            $query->where('created_at', '>=', $this->from);
        }

        return $query->get()->map(function ($deliveryPolicy) {
            $carNumber = $deliveryPolicy->car->car_number ?? '';
            $containerNumbers = $deliveryPolicy->booking_containers 
                ? implode(', ', $deliveryPolicy->booking_containers->pluck('container_no')->toArray() ?? []) 
                : '';
            $cost = is_numeric($deliveryPolicy->cost) ? (float)$deliveryPolicy->cost : 0;
            $financialCustody = $deliveryPolicy->money_transfer->value ?? 0;
            $extraExpense = $deliveryPolicy->extraExpenses->sum('value') ?? 0;
            $payments = $deliveryPolicy->payingCars->sum('value') ?? 0;
            $thePayer = $payments + (int) $financialCustody;
            // حساب المتبقي:
            // إذا كان هناك cost: المتبقي = cost - العهدة + المصروفات الإضافية - المدفوعات
            // إذا لم يكن هناك cost: المتبقي = المصروفات الإضافية + المدفوعات - العهدة (لأن العهدة دين على السيارة)
            $remain = $cost 
                ? ($cost - $financialCustody + $extraExpense - $payments)
                : ($extraExpense + $payments - $financialCustody);

            return [
                'id' => $deliveryPolicy->id,
                'car_number' => $carNumber,
                'container_no' => $containerNumbers,
                'date' => $deliveryPolicy->date,
                'costing' => $cost,
                'financial_custody' => $financialCustody,
                'extra_expense' => $extraExpense,
                'the_payer' => $thePayer,
                'departure' => $deliveryPolicy->booking_containers->first()->departure->title ?? '',
                'loading' => $deliveryPolicy->booking_containers->first()->loading->title ?? '',
                'aging' => $deliveryPolicy->booking_containers->first()->aging->title ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            '#',
            __('admin.car_number'),
            __('admin.container_no'),
            'تاريخ النقلة',
            __('admin.costing'),
            __('admin.financial_custody'),
            __('main.extra_expense'),
            __('the_payer'),
            __('admin.departure'),
            __('admin.loading'),
            __('admin.aging')
        ];
    }
}
