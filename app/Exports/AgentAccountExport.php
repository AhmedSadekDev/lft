<?php

namespace App\Exports;

use App\Models\Agent;
use App\Models\AgentExpense;
use App\Models\MoneyTransfer;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AgentAccountExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(private Agent $agent)
    {
    }

    public function collection()
    {
        $incoming = $this->agent->financial_custodies()->with('transferer')->get();
        $outgoing = $this->agent->sended_financial_custodies()->with(['transferer', 'transfered'])->get();
        $expenses = $this->agent->expenses()
            ->with(['booking', 'service.serviceCategory'])
            ->get();

        $transfers = $incoming->concat($outgoing)->unique('id');

        return $transfers->concat($expenses)
            ->sortBy(function ($row) {
                $raw = method_exists($row, 'getRawOriginal') ? $row->getRawOriginal('created_at') : $row->created_at;

                return $raw ? Carbon::parse($raw)->timestamp : 0;
            })
            ->values()
            ->map(function ($row) {
                $raw = method_exists($row, 'getRawOriginal') ? $row->getRawOriginal('created_at') : ($row->created_at ?? null);
                $date = $raw ? Carbon::parse($raw)->format('Y-m-d H:i:s') : '';

                if ($row instanceof AgentExpense) {
                    $pending = $row->admin_approval == 0 || $row->admin_approval === null;

                    return [
                        'id' => $row->id,
                        'date' => $date,
                        'kind' => 'مصروف',
                        'title' => $row->title ?? '',
                        'amount' => $row->value,
                        'status' => $pending ? 'بانتظار المراجعة' : 'تمت المراجعة',
                        'notes' => $row->booking?->booking_number ? ('طلب ' . $row->booking->booking_number) : '',
                    ];
                }

                /** @var MoneyTransfer $row */
                $type = (int) $row->type;
                $kind = match ($type) {
                    MoneyTransfer::fromDashboard => 'عهدة واردة',
                    MoneyTransfer::transferAgent => 'تحويل مندوب',
                    MoneyTransfer::vaultReturnPending => 'إرجاع خزنة (معلّق)',
                    MoneyTransfer::vaultReturnApproved => 'إرجاع خزنة (معتمد)',
                    MoneyTransfer::deliveryPolicy => 'عهدة نقلة',
                    MoneyTransfer::settle => 'تسوية',
                    MoneyTransfer::officeCommission => 'دخان مكتب',
                    default => 'تحويل #' . $type,
                };
                $status = match ($type) {
                    MoneyTransfer::vaultReturnPending => 'بانتظار المراجعة',
                    MoneyTransfer::vaultReturnApproved => 'تمت المراجعة',
                    default => '—',
                };

                return [
                    'id' => $row->id,
                    'date' => $date,
                    'kind' => $kind,
                    'title' => $row->title ?? '',
                    'amount' => $row->value,
                    'status' => $status,
                    'notes' => $row->notes ?? '',
                ];
            });
    }

    public function headings(): array
    {
        return [
            '#',
            'التاريخ',
            'النوع',
            'الوصف',
            'المبلغ',
            'الحالة',
            'ملاحظات',
        ];
    }
}
