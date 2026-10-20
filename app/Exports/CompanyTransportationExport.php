<?php

namespace App\Exports;

use App\Models\CompanyTransportation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

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

        // Same English keys + container full_name as the import template expects.
        return $query->get()->map(function ($transportation) {
            return [
                'company' => $transportation->company?->name ?? '',
                'container' => $transportation->container?->full_name ?? '',
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
            'company',
            'container',
            'departure',
            'loading',
            'aging',
            'price',
        ];
    }
}
