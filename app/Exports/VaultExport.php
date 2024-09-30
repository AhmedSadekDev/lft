<?php

namespace App\Exports;

use App\Models\Vault;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VaultExport implements FromCollection, ShouldAutoSize, WithHeadings
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
        return Vault::whereIn('id', $this->ids)->get()->map(function ($bank) {
            return [
                'id' => $bank->id,
                'name' => $bank->name,
                'amount' => $bank->amount,
                'type' => $bank->type_name,
                'note' => $bank->note
            ];
        });
    }


    public function headings(): array
    {
        return [
            '#',
            __('main.operation_name'),
            __('main.amount'),
            __('admin.type'),
            __('admin.note')
        ];
    }
}
