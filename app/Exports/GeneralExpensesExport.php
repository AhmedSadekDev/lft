<?php

namespace App\Exports;

use App\Models\AgentExpense;
use App\Models\BankTrnsaction;
use App\Models\LogActivity;
use App\Models\MoneyTransfer;
use App\Models\Payingcar;
use App\Models\VaultTransaction;
use App\Services\GeneralExpensesReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GeneralExpensesExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(protected Request $request)
    {
    }

    public function collection()
    {
        $built = app(GeneralExpensesReportService::class)->build($this->request);

        return $built['expenses']->values()->map(function ($item, $index) {
            $data = $this->extractRowData($item);

            return [
                $index + 1,
                $data['category'],
                $data['service'],
                $data['expenseValue'] > 0 ? $data['expenseValue'] : '-',
                $data['incomeValue'] > 0 ? $data['incomeValue'] : '-',
                $data['bookingNumber'] ?: '-',
                $data['date'],
                $data['agentName'],
                $data['notes'] ?: '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'م',
            'الفئة',
            'الخدمة',
            'القيمة منصرف',
            'القيمة وارد',
            'رقم الشحنة',
            'التاريخ',
            'المندوب',
            'الملاحظات',
        ];
    }

    private function extractRowData($item): array
    {
        $data = [
            'category' => '',
            'service' => '',
            'expenseValue' => 0,
            'incomeValue' => 0,
            'agentName' => '',
            'bookingNumber' => '',
            'date' => '',
            'notes' => '',
        ];

        if ($item instanceof LogActivity) {
            $logType = $item->log_type;
            $log = $item->log;
            $value = $log?->value ?? 0;

            $data['agentName'] = $item->attacher?->name ?? '-';
            $data['date'] = $item->created_at ? Carbon::parse($item->created_at)->format('Y-m-d H:i') : '';

            if ($logType == AgentExpense::class) {
                $data['expenseValue'] = $value;
                $data['category'] = 'سجل نشاط - مصروف';
                $data['service'] = $item->action ?? 'مصروف مندوب';

                if ($log && $log->bookingContainer) {
                    $data['bookingNumber'] = $log->bookingContainer->booking?->booking_number ?? '';
                }

                if ($log && isset($log->notes)) {
                    $data['notes'] = $log->notes ?? '';
                }
            } elseif ($logType == MoneyTransfer::class && $log) {
                $type = (int) $log->type;

                if ($type === MoneyTransfer::deliveryPolicy) {
                    $data['expenseValue'] = $value;
                    $data['category'] = 'سجل نشاط - عهدة السيارة';
                    $data['service'] = $item->action ?? 'تحويل عهدة';

                    if ($log->delivery_policy_id && $log->delivery_policy) {
                        $firstContainer = $log->delivery_policy->booking_containers->first();
                        if ($firstContainer) {
                            $data['bookingNumber'] = $firstContainer->booking?->booking_number ?? '';
                        }
                    }
                } elseif ($type === MoneyTransfer::officeCommission) {
                    $data['incomeValue'] = $value;
                    $data['category'] = 'سجل نشاط - دخان المكتب';
                    $data['service'] = $item->action ?? 'دخان المكتب';

                    if ($log->delivery_policy_id && $log->delivery_policy) {
                        $firstContainer = $log->delivery_policy->booking_containers->first();
                        if ($firstContainer) {
                            $data['bookingNumber'] = $firstContainer->booking?->booking_number ?? '';
                        }
                    }
                } elseif ($type === MoneyTransfer::fromDashboard) {
                    $data['incomeValue'] = $value;
                    $data['category'] = 'سجل نشاط - إيداع';
                    $data['service'] = $item->action ?? 'إيداع من لوحة التحكم';
                } elseif ($type === MoneyTransfer::transferAgent) {
                    $data['expenseValue'] = $value;
                    $data['category'] = 'سجل نشاط - تحويل';
                    $data['service'] = $item->action ?? 'تحويل لمندوب آخر';
                } elseif ($type === MoneyTransfer::settle) {
                    $data['expenseValue'] = $value;
                    $data['category'] = 'سجل نشاط - تسوية';
                    $data['service'] = $item->action ?? 'تسوية البوليصة';
                }

                if (isset($log->notes)) {
                    $data['notes'] = $log->notes ?? '';
                }
            }
        } elseif ($item instanceof MoneyTransfer && (int) $item->type === MoneyTransfer::deliveryPolicy) {
            $data['expenseValue'] = $item->value ?? 0;
            $data['category'] = 'عهدة السيارة';
            $data['service'] = 'تحويل عهدة للسائق ' . ($item->transfered?->name ?? '');
            $data['agentName'] = $item->transferer?->name ?? '-';
            $data['date'] = $item->created_at ? Carbon::parse($item->created_at)->format('Y-m-d H:i') : '';

            if ($item->delivery_policy_id && $item->delivery_policy) {
                $firstContainer = $item->delivery_policy->booking_containers->first();
                if ($firstContainer) {
                    $data['bookingNumber'] = $firstContainer->booking?->booking_number ?? '';
                }
            }

            $data['notes'] = $item->notes ?? '';
        } elseif ($item instanceof MoneyTransfer && (int) $item->type === MoneyTransfer::officeCommission) {
            $data['incomeValue'] = $item->value ?? 0;
            $data['category'] = 'دخان المكتب';
            $data['service'] = 'عهدة السيارة';
            $data['agentName'] = $item->transfered?->name ?? '-';
            $data['date'] = $item->created_at ? Carbon::parse($item->created_at)->format('Y-m-d H:i') : '';

            if ($item->delivery_policy_id && $item->delivery_policy) {
                $firstContainer = $item->delivery_policy->booking_containers->first();
                if ($firstContainer) {
                    $data['bookingNumber'] = $firstContainer->booking?->booking_number ?? '';
                }
            }

            $data['notes'] = $item->notes ?? '';
        } elseif ($item instanceof AgentExpense) {
            $data['expenseValue'] = $item->value ?? 0;

            if ($item->delivery_policy_id || (int) $item->type === 2) {
                $data['category'] = 'عهدة السيارة';
            } else {
                $data['category'] = 'من إدخال المندوب';
            }

            if ($item->service) {
                $data['service'] = ($item->service->serviceCategory?->title ?? '') . ' - ' . ($item->service->name ?? '');
            } else {
                $data['service'] = '-';
            }

            $data['agentName'] = $item->responsible_name ?: '-';
            $data['bookingNumber'] = $item->bookingContainer?->booking?->booking_number ?? '';
            $createdAt = $item->getRawOriginal('created_at');
            $data['date'] = $createdAt ? Carbon::parse($createdAt)->format('Y-m-d H:i') : '';
            $data['notes'] = $item->notes ?? '';
        } elseif ($item instanceof Payingcar) {
            $data['expenseValue'] = (float) ($item->value ?? 0);
            $data['category'] = 'سداد المديونية للسيارات';
            $data['service'] = 'سداد مديونية السيارة - ' . ($item->car?->car_number ?? '');
            $data['agentName'] = $item->user?->name ?? '-';
            $data['date'] = $item->created_at ? Carbon::parse($item->created_at)->format('Y-m-d H:i') : '';

            if ($item->delivery_policy_id && $item->delivery_policy) {
                $firstContainer = $item->delivery_policy->booking_containers->first();
                if ($firstContainer) {
                    $data['bookingNumber'] = $firstContainer->booking?->booking_number ?? '';
                }
            }

            $data['notes'] = $item->notes ?? '';
        } elseif ($item instanceof VaultTransaction && (int) $item->type === VaultTransaction::dashboardExpense) {
            $data['expenseValue'] = (float) ($item->amount ?? 0);
            $data['category'] = 'مصروف داشبورد - خزنة';
            $data['service'] = $item->name ?? '-';
            $data['agentName'] = 'الخزنة';
            $data['date'] = $item->created_at ? Carbon::parse($item->created_at)->format('Y-m-d H:i') : '';
            $data['notes'] = $item->name ?? '';
        } elseif ($item instanceof BankTrnsaction && (int) $item->type === BankTrnsaction::dashboardExpense) {
            $data['expenseValue'] = (float) ($item->amount ?? 0);
            $data['category'] = 'مصروف داشبورد - بنك';
            $data['service'] = $item->name ?? '-';
            $data['agentName'] = $item->user?->name ?: ($item->bank?->name ?? '-');
            $data['date'] = $item->created_at ? Carbon::parse($item->created_at)->format('Y-m-d H:i') : '';
            $data['notes'] = ($item->bank?->name ? ('بنك: ' . $item->bank->name) : '') .
                ($item->name ? (($item->bank?->name ? ' — ' : '') . $item->name) : '');
        }

        return $data;
    }
}
