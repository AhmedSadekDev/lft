<?php

namespace App\Exports;

use App\Models\Agent;
use App\Models\AgentExpense;
use App\Models\MoneyTransfer;
use App\Services\AgentCustodyStatementService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AgentAccountExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(
        private Agent $agent,
        private ?string $from = null,
        private ?string $to = null,
    ) {
    }

    public function collection()
    {
        $statement = app(AgentCustodyStatementService::class)->build($this->agent, $this->from, $this->to);

        $rows = collect();
        if ($this->from || $this->to) {
            $rows->push([
                'id' => '',
                'date' => '',
                'kind' => 'رصيد افتتاحي',
                'title' => '',
                'amount' => '',
                'status' => '',
                'balance_before' => '',
                'balance_after' => $statement['opening_balance'],
                'notes' => '',
            ]);
        }

        foreach ($statement['rows'] as $row) {
            $model = $row['model'];
            $raw = method_exists($model, 'getRawOriginal')
                ? ($model->getRawOriginal('created_at') ?? null)
                : ($model->created_at ?? null);
            $date = $raw ? Carbon::parse($raw)->format('Y-m-d H:i:s') : '';
            $effective = $row['effective_at']->format('Y-m-d H:i:s');

            if ($model instanceof AgentExpense) {
                $pending = $model->admin_approval == 0 || $model->admin_approval === null;

                $rows->push([
                    'id' => $model->id,
                    'date' => $date,
                    'kind' => 'مصروف',
                    'title' => $model->title ?? '',
                    'amount' => $model->value,
                    'status' => $pending ? 'بانتظار المراجعة' : 'تمت المراجعة',
                    'balance_before' => $row['balance_before'],
                    'balance_after' => $row['balance_after'],
                    'notes' => trim(
                        ($model->booking?->booking_number ? ('طلب ' . $model->booking->booking_number) : '')
                        . ($effective !== $date ? (' | تأثير الرصيد: ' . $effective) : '')
                    ),
                ]);

                continue;
            }

            /** @var MoneyTransfer $model */
            $type = (int) $model->type;
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

            $rows->push([
                'id' => $model->id,
                'date' => $date,
                'kind' => $kind,
                'title' => $model->title ?? '',
                'amount' => $model->value,
                'status' => $status,
                'balance_before' => $row['balance_before'],
                'balance_after' => $row['balance_after'],
                'notes' => $model->notes ?? '',
            ]);
        }

        $rows->push([
            'id' => '',
            'date' => '',
            'kind' => 'رصيد ختامي',
            'title' => '',
            'amount' => '',
            'status' => '',
            'balance_before' => '',
            'balance_after' => $statement['closing_balance'],
            'notes' => '',
        ]);

        return $rows;
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
            'رصيد قبل',
            'رصيد بعد',
            'ملاحظات',
        ];
    }
}
