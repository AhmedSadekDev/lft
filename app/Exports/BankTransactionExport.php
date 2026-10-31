<?php

namespace App\Exports;

use App\Models\BankTrnsaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BankTransactionExport implements FromCollection, ShouldAutoSize, WithHeadings
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
        return BankTrnsaction::with(['bank', 'user', 'transBank', 'company'])
            ->whereIn('id', $this->ids)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'date' => $item->date,
                    'amount' => $item->amount,
                    'type' => $item->typeText(),
                    'image' => $item->image ? asset($item->image) : '',
                    'bank' => $item->bank?->name ?? '',
                    'addedby' => $item->user?->name ?? '',
                ];
            });
    }

    public function headings(): array
    {
        return [
            '#',
            __('admin.name'),
            __('main.date'),
            __('main.amount'),
            __('admin.type'),
            __('admin.image'),
            __('admin.bank'),
            __('main.added_by')
        ];
    }
}
