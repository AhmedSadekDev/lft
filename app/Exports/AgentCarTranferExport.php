<?php

namespace App\Exports;

use App\Models\AgentCarTranfer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AgentCarTranferExport implements FromCollection, ShouldAutoSize, WithHeadings
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
        return AgentCarTranfer::whereIn('id', $this->ids)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'car_number' => $item->car->car_number,
                'agent' => $item->agent->name,
                'name' => $item->name,
                'value' => $item->value,
                'date' => $item->created_at,
                'user' => $item->user ? $item->user->name : "",
            ];
        });
    }

    public function headings(): array
    {
        return [
            '#',
            __('admin.car_number'),
            __('main.agent'),
            __('admin.name'),
            __('admin.value'),
            __('main.date'),
            __('main.added_by')
        ];
    }
}
