<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use App\Models\BookingContainer;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class BookingContainerDetails implements FromCollection, WithHeadings, ShouldAutoSize
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
        return BookingContainer::whereIn('id', $this->ids)->get()->map(function ($item) {
            switch ($item->booking->type_of_action) {
                case 0:
                    $typePolicy = 'تصدير';
                    break;

                case 1:
                    $typePolicy = 'إستيراد';
                    break;

                case 2:
                    $typePolicy = 'تخليص جمركي';
                    break;

                default:
                    $typePolicy = 'unknown';
                    break;
            }

            $containerNo = $item->container_no;
            if (empty($containerNo)) {
                $firstPolicy = $item->delivery_policies->first();
                // Safely read first related container_no from first delivery policy, else fallback to sail_of_number
                $containerNo = data_get($firstPolicy, 'booking_containers.0.container_no') ?: $item->sail_of_number;
            }

            return [
                'id' => $item->booking_id,
                'date' => $item->created_at ?? $item->updated_at,
                'invoice_no' => $item->booking ? ($item->booking->invoice ? $item->booking->invoice->invoice_number : null) : null,
                'company_name' => $item->booking ? ($item->booking->company ? $item->booking->company->name : null) : null,
                'container_no' => $containerNo,
                'factory' => $item->booking ? ($item->booking->factory ? $item->booking->factory->name : null) : null,
                'booking_number' => $item->booking ? $item->booking->booking_number : null,
                'certificate_number' => $item->booking->certificate_number ?? '',
                'ContainerType' => $item->ContainerType,
                'policy' => optional(optional($item->delivery_policies->first())->money_transfer)->value ?? '',
                'type_of_sail' => $typePolicy,
                'sail_name' => $item->booking->shippingAgent->title ?? '',
                'sail_of_number' => $item->sail_of_number,
                'car' => optional(optional($item->delivery_policies->first())->car)->car_number ?? '',
                'drive' => optional(optional($item->delivery_policies->first())->driver)->name ?? '',
                'drive_phone' => optional(optional($item->delivery_policies->first())->driver)->phone ?? '',
                // خروج: تاريخ الخروج من أول بوليصة
                'departure' => optional($item->delivery_policies->first())->date ?? null,
                'loading' => $item->Loading ? $item->Loading->title : null,
                'aging_id' => $item->Aging ? $item->Aging->title : null,
                'total' => $item->price,
                'deliverd' => $item->delivery_policies->first() && $item->delivery_policies->first()->payingCar ? $item->delivery_policies->first()->payingCar->value : null,
                'remain' => $item->price - optional(optional($item->delivery_policies->first())->payingCar)->value ?? 0

            ];
        });
    }



    public function headings(): array
    {
        return [
            'مسلسل الطلب',
            'تاريخ الطلب',
            'رقم الفاتورة',
            'الشركة',
            'رقم الحاوية',
            'المصنع',
            'رقم الحجز',
            'رقم الشهاده',
            'نوع و حجم الحاوية',
            'بوليصة المكتب',
            'نوع البوليصة',
            'الخط الملاحى',
            'السيل الملاحى',
            'السياره',
            'السائق',
            'رقم هاتف السائق',
            'خروج',
            'تحميل',
            'تعتيق',
            'التكلفه',
            'المسدد',
            'المتبقى'
        ];
    }
}
