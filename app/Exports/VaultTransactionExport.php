<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\VaultTransaction;

class VaultTransactionExport implements FromCollection, ShouldAutoSize, WithHeadings
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
         return VaultTransaction::whereIn('id', $this->ids)->where('type', 0)->get()->map(function($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'amount' => $item->amount,
                'type' => $item->type ? __('main.debit') : __('main.credit'),
                'bank' => $item->bank?->name ?? "N/A",
            ];
        });
    }
    
    
    public function headings(): array
    {
        return [
            '#',
            __('admin.name'),
            __('main.amount'),
            __('admin.type'),
            __('admin.bank'),
        ];
    }
}
