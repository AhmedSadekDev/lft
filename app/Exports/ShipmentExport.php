<?php

namespace App\Exports;

use App\Models\DeliveryPolicy;
use App\Models\Shipment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ShipmentExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public $ids;

    public function __construct($ids)
    {
        $this->ids = $ids;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return DeliveryPolicy::whereIn('id', $this->ids)
            ->with(['car', 'booking_containers', 'money_transfer', 'extraExpenses', 'payingCars'])
            ->get()
            ->map(function ($deliveryPolicy) {
                $carNumber = $deliveryPolicy->car->car_number ?? '';
                $containerNumbers = $deliveryPolicy->booking_containers ? implode(', ', $deliveryPolicy->booking_containers->pluck('container_no')->toArray() ?? []) : '';
                $cost = $deliveryPolicy->cost ?? 'لم يتم التحديد بعد';
                $financialCustody = $deliveryPolicy->money_transfer->value ?? 0;
                $extraExpense = $deliveryPolicy->extraExpenses->sum('value') ?? 0;
                $thePayer = $deliveryPolicy->payingCars->sum('value') + $financialCustody;

                $remain = $cost ? (($cost - $financialCustody + $extraExpense) - $deliveryPolicy->payingCars->sum('value'))
                    : ($financialCustody + $extraExpense - $deliveryPolicy->payingCars->sum('value'));

                return [
                    'id' => $deliveryPolicy->id,
                    'car_number' => $carNumber,
                    'container_no' => $containerNumbers,
                    'costing' => $cost,
                    'financial_custody' => $financialCustody,
                    'extra_expense' => $extraExpense,
                    'the_payer' => $thePayer,
                    'the_rest' => $remain,
                ];
            });
    }



    public function headings(): array
    {
        return [
            '#',
            __('admin.car_number'),
            __('admin.container_no'),
            __('admin.costing'),
            __('admin.financial_custody'),
            __('main.extra_expense'),
            __('the_payer'),
            __('the_rest'),
        ];
    }
}
