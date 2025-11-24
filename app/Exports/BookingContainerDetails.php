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
        return BookingContainer::whereIn('id', $this->ids)
            ->with(['container', 'delivery_policies.money_transfer', 'delivery_policies'])
            ->get()
            ->map(function ($item) {
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

                // الحصول على نوع وحجم الحاوية
                $containerType = $item->container?->type ?? '';
                $containerSize = $item->container?->size ?? '';
                $containerTypeAndSize = '';
                if ($containerType && $containerSize) {
                    $containerTypeAndSize = $containerSize . ' - ' . $containerType;
                } elseif ($containerType) {
                    $containerTypeAndSize = $containerType;
                } elseif ($containerSize) {
                    $containerTypeAndSize = $containerSize;
                } else {
                    $containerTypeAndSize = $item->ContainerType ?? '';
                }

                $firstPolicy = $item->delivery_policies->first();

                // عهدة السيارة (type 3)
                $carCustody = optional(optional($firstPolicy)->money_transfer)->value ?? 0;

                // دخان المكتب (type 5) - office commission
                $officeCommission = 0;
                if ($firstPolicy) {
                    $officeCommissionTransfer = \App\Models\MoneyTransfer::where('delivery_policy_id', $firstPolicy->id)
                        ->where('type', \App\Models\MoneyTransfer::officeCommission)
                        ->first();
                    $officeCommission = $officeCommissionTransfer?->value ?? ($firstPolicy->office_commission ?? 0);
                }

                // المسدد (عهدة السيارة)
                $paidAmount = $firstPolicy && $firstPolicy->payingCar ? $firstPolicy->payingCar->value : 0;

                // المتبقي من تكلفة النقل
                $remainingAmount = $item->price - $paidAmount;

                return [
                    'id' => $item->booking_id,
                    'date' => $item->created_at ?? $item->updated_at,
                    'invoice_no' => $item->booking ? ($item->booking->invoice ? $item->booking->invoice->invoice_number : null) : null,
                    'company_name' => $item->booking ? ($item->booking->company ? $item->booking->company->name : null) : null,
                    'container_no' => $containerNo,
                    'factory' => $item->booking ? ($item->booking->factory ? $item->booking->factory->name : null) : null,
                    'booking_number' => $item->booking ? $item->booking->booking_number : null,
                    'certificate_number' => $item->booking->certificate_number ?? '',
                    'container_type' => $containerType,
                    'container_size' => $containerSize,
                    'container_type_and_size' => $containerTypeAndSize,
                    'office_commission' => $officeCommission, // دخان المكتب
                    'car_custody' => $carCustody, // عهدة السيارة
                    'type_of_sail' => $typePolicy,
                    'sail_name' => $item->booking->shippingAgent->title ?? '',
                    'sail_of_number' => $item->sail_of_number,
                    'car' => optional(optional($firstPolicy)->car)->car_number ?? '',
                    'drive' => optional(optional($firstPolicy)->driver)->name ?? '',
                    'drive_phone' => optional(optional($firstPolicy)->driver)->phone ?? '',
                    // خروج: تاريخ الخروج من أول بوليصة
                    'departure' => optional($firstPolicy)->date ?? null,
                    'loading' => $item->Loading ? $item->Loading->title : null,
                    'aging_id' => $item->Aging ? $item->Aging->title : null,
                    'total' => $item->price,
                    'paid_amount' => $paidAmount, // المسدد (عهدة السيارة)
                    'remaining_amount' => $remainingAmount // المتبقي من تكلفة النقل
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
            'نوع الحاوية',
            'حجم الحاوية (المقاس)',
            'نوع و حجم الحاوية',
            'دخان المكتب',
            'عهدة السيارة',
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
            'المسدد (عهدة السيارة)',
            'المتبقي من تكلفة النقل'
        ];
    }
}
