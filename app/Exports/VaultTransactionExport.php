<?php

namespace App\Exports;

use App\Models\VaultTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VaultTransactionExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    /** @var array<int>|null */
    public $ids;

    public function __construct(
        $ids = null,
        private ?Request $request = null,
    ) {
        $this->ids = is_array($ids) ? $ids : (is_string($ids) && $ids !== '' ? explode(',', $ids) : null);
    }

    public function collection()
    {
        $query = VaultTransaction::with(['bank', 'agient']);

        if ($this->ids) {
            $query->whereIn('id', $this->ids);
        }

        if ($this->request?->filled('from')) {
            $query->where('created_at', '>=', $this->request->from);
        }
        if ($this->request?->filled('to')) {
            $query->where('created_at', '<=', $this->request->to);
        }

        return $query->latest()->get()->map(function (VaultTransaction $item) {
            $raw = $item->getRawOriginal('created_at') ?? $item->created_at;

            return [
                'id' => $item->id,
                'name' => $item->name,
                'amount' => $item->amount,
                'type' => $item->type ? __('main.debit') : __('main.credit'),
                'bank' => $item->bank?->name ?? '-',
                'agent' => $item->agient?->name ?? '-',
                'date' => $raw ? Carbon::parse($raw)->format('Y-m-d H:i:s') : '',
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
            'المندوب',
            __('main.date'),
        ];
    }
}
