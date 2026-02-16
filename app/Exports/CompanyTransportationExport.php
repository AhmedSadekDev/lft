<?php

namespace App\Exports;

use App\Models\CompanyTransportation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CompanyTransportationExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $companyId;

    public function __construct($companyId = null)
    {
        $this->companyId = $companyId;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = CompanyTransportation::with(['company', 'container', 'Departure', 'Loading', 'Aging']);

        if ($this->companyId) {
            $query->where('company_id', $this->companyId);
        }

        return $query->get()->map(function ($transportation) {
            return [
                'id' => $transportation->id,
                'company_name' => $transportation->company?->name ?? '',
                'container_type' => $transportation->container?->full_name ?? '',
                'departure' => $transportation->Departure?->title ?? '',
                'loading' => $transportation->Loading?->title ?? '',
                'aging' => $transportation->Aging?->title ?? '',
                'price' => $transportation->price ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            '#',
            __('main.company'),
            __('main.container'),
            __('admin.departure_location'),
            __('admin.loading_location'),
            __('admin.aging_location'),
            __('admin.price'),
        ];
    }
}
