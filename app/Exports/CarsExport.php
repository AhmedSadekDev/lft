<?php 
namespace App\Exports;

use App\Models\Car;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CarsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Car::with('deliveryPolicies', 'payingcars')->get()->map(function ($car) {
            return [
                'ID' => $car->id,
                'رقم السيارة' => $car->car_number,
                'تاريخ آخر نقلة' => optional($car->deliveryPolicies()->latest()->first())->created_at ?? "لا توجد نقلة",
                'إجمالي الحساب' => $car->deliveryPolicies->sum('cost') - $car->payingcars->sum('value'),
            ];
        });
    }

    public function headings(): array
    {
        return ['ID', 'رقم السيارة', 'تاريخ آخر نقلة', 'إجمالي الحساب'];
    }
}
?>