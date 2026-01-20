<?php

namespace App\Exports;

use App\Models\AgentExpense;
use App\Models\LogActivity;
use App\Models\MoneyTransfer;
use App\Models\Payingcar;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class GeneralExpensesExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        // جلب البيانات من جميع المصادر (بدون pagination)
        $agentExpenses = $this->getAgentExpenses($this->request);
        $deliveryPolicies = $this->getDeliveryPolicies($this->request);
        $officeCommissions = $this->getOfficeCommissions($this->request);
        $payingCars = $this->getPayingCars($this->request);
        $logActivities = $this->getLogActivities($this->request);

        // تصفية LogActivity لإزالة التكرار
        $filteredLogActivities = $this->filterDuplicateLogActivities(
            $logActivities,
            $agentExpenses,
            $deliveryPolicies,
            $officeCommissions,
            $payingCars
        );

        // دمج جميع البيانات
        $expenses = $agentExpenses
            ->concat($deliveryPolicies)
            ->concat($officeCommissions)
            ->concat($payingCars)
            ->concat($filteredLogActivities)
            ->sortByDesc('created_at');

        // تطبيق البحث
        if ($this->request->filled('search')) {
            $expenses = $this->applySearch($expenses, $this->request->search);
        }

        // تحويل البيانات إلى صيغة Excel
        return $expenses->map(function ($item, $index) {
            $data = $this->extractRowData($item);
            return [
                $index + 1, // رقم الصف
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

    /**
     * استخراج بيانات الصف من السجل
     */
    private function extractRowData($item)
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

        $isMoneyTransfer = $item instanceof MoneyTransfer;
        $isExpense = $item instanceof AgentExpense;
        $isLogActivity = $item instanceof LogActivity;
        $isPayingcar = $item instanceof Payingcar;

        if ($isLogActivity) {
            $logType = $item->log_type;
            $log = $item->log;
            $value = $log?->value ?? 0;

            $data['agentName'] = $item->attacher?->name ?? '-';
            $data['date'] = $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') : '';

            if ($logType == AgentExpense::class) {
                $data['expenseValue'] = $value;
                $data['category'] = 'سجل نشاط - مصروف';
                $data['service'] = $item->action ?? 'مصروف مندوب';

                if ($log && $log->bookingContainer) {
                    $data['bookingNumber'] = $log->bookingContainer->booking?->booking_number ?? '';
                }

                // الحصول على الملاحظات
                if ($log && isset($log->notes)) {
                    $data['notes'] = $log->notes ?? '';
                }
            } elseif ($logType == MoneyTransfer::class && $log) {
                $type = $log->type;

                if ($type == MoneyTransfer::deliveryPolicy) {
                    $data['expenseValue'] = $value;
                    $data['category'] = 'سجل نشاط - عهدة السيارة';
                    $data['service'] = $item->action ?? 'تحويل عهدة';

                    if ($log->delivery_policy_id && $log->delivery_policy) {
                        $firstContainer = $log->delivery_policy->booking_containers->first();
                        if ($firstContainer) {
                            $data['bookingNumber'] = $firstContainer->booking?->booking_number ?? '';
                        }
                    }
                } elseif ($type == MoneyTransfer::officeCommission) {
                    $data['incomeValue'] = $value;
                    $data['category'] = 'سجل نشاط - دخان المكتب';
                    $data['service'] = $item->action ?? 'دخان المكتب';

                    if ($log->delivery_policy_id && $log->delivery_policy) {
                        $firstContainer = $log->delivery_policy->booking_containers->first();
                        if ($firstContainer) {
                            $data['bookingNumber'] = $firstContainer->booking?->booking_number ?? '';
                        }
                    }
                } elseif ($type == MoneyTransfer::fromDashboard) {
                    $data['incomeValue'] = $value;
                    $data['category'] = 'سجل نشاط - إيداع';
                    $data['service'] = $item->action ?? 'إيداع من لوحة التحكم';
                } elseif ($type == MoneyTransfer::transferAgent) {
                    $data['expenseValue'] = $value;
                    $data['category'] = 'سجل نشاط - تحويل';
                    $data['service'] = $item->action ?? 'تحويل لمندوب آخر';
                } elseif ($type == MoneyTransfer::settle) {
                    $data['expenseValue'] = $value;
                    $data['category'] = 'سجل نشاط - تسوية';
                    $data['service'] = $item->action ?? 'تسوية البوليصة';
                }

                // الحصول على الملاحظات من MoneyTransfer
                if ($log && isset($log->notes)) {
                    $data['notes'] = $log->notes ?? '';
                }
            }
        } elseif ($isMoneyTransfer && $item->type == 3) {
            $data['expenseValue'] = $item->value ?? 0;
            $data['category'] = 'عهدة السيارة';
            $data['service'] = 'تحويل عهدة للسائق ' . ($item->transfered?->name ?? '');
            $data['agentName'] = $item->transferer?->name ?? '-';
            $data['date'] = $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') : '';

            if ($item->delivery_policy_id && $item->delivery_policy) {
                $firstContainer = $item->delivery_policy->booking_containers->first();
                if ($firstContainer) {
                    $data['bookingNumber'] = $firstContainer->booking?->booking_number ?? '';
                }
            }

            // الحصول على الملاحظات
            $data['notes'] = $item->notes ?? '';
        } elseif ($isMoneyTransfer && $item->type == 5) {
            $data['incomeValue'] = $item->value ?? 0;
            $data['category'] = 'دخان المكتب';
            $data['service'] = 'عهدة السيارة';
            $data['agentName'] = $item->transfered?->name ?? '-';
            $data['date'] = $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') : '';

            if ($item->delivery_policy_id && $item->delivery_policy) {
                $firstContainer = $item->delivery_policy->booking_containers->first();
                if ($firstContainer) {
                    $data['bookingNumber'] = $firstContainer->booking?->booking_number ?? '';
                }
            }

            // الحصول على الملاحظات
            $data['notes'] = $item->notes ?? '';
        } elseif ($isExpense) {
            $data['expenseValue'] = $item->value ?? 0;

            if ($item->delivery_policy_id) {
                $data['category'] = 'عهدة السيارة';
            } elseif ($item->type == 2) {
                $data['category'] = 'عهدة السيارة';
            } else {
                $data['category'] = 'من إدخال المندوب';
            }

            if ($item->service) {
                $data['service'] = ($item->service->serviceCategory?->title ?? '') . ' - ' . ($item->service->name ?? '');
            } else {
                $data['service'] = '-';
            }

            $data['agentName'] = $item->agent?->name ?? '-';
            $data['bookingNumber'] = $item->bookingContainer?->booking?->booking_number ?? '';
            $data['date'] = $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') : '';

            // الحصول على الملاحظات
            $data['notes'] = $item->notes ?? '';
        } elseif ($isPayingcar) {
            $data['expenseValue'] = (float) ($item->value ?? 0);
            $data['category'] = 'سداد المديونية للسيارات';
            $data['service'] = 'سداد مديونية السيارة - ' . ($item->car?->car_number ?? '');
            $data['agentName'] = $item->user?->name ?? '-';
            $data['date'] = $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') : '';

            if ($item->delivery_policy_id && $item->delivery_policy) {
                $firstContainer = $item->delivery_policy->booking_containers->first();
                if ($firstContainer) {
                    $data['bookingNumber'] = $firstContainer->booking?->booking_number ?? '';
                }
            }

            // الحصول على الملاحظات
            $data['notes'] = $item->notes ?? '';
        }

        return $data;
    }

    /**
     * تطبيق فلترة التاريخ على Query
     */
    private function applyDateFilter($query, $from, $to)
    {
        return $query
            ->when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn($q) => $q->where('created_at', '<=', $to));
    }

    /**
     * جلب مصروفات المندوبين
     */
    private function getAgentExpenses(Request $request)
    {
        $query = AgentExpense::with([
            'agent',
            'service.serviceCategory',
            'bookingContainer.booking',
            'delivery_policy'
        ]);

        return $this->applyDateFilter($query, $request->from, $request->to)
            ->latest()
            ->get();
    }

    /**
     * جلب معاملات عهدة السيارة
     */
    private function getDeliveryPolicies(Request $request)
    {
        $query = MoneyTransfer::with([
            'transferer',
            'transfered',
            'delivery_policy.booking_containers.booking',
            'delivery_policy.image'
        ])->where('type', MoneyTransfer::deliveryPolicy);

        return $this->applyDateFilter($query, $request->from, $request->to)
            ->latest()
            ->get();
    }

    /**
     * جلب معاملات دخان المكتب
     */
    private function getOfficeCommissions(Request $request)
    {
        $query = MoneyTransfer::with([
            'transfered',
            'delivery_policy.booking_containers.booking',
            'delivery_policy.image'
        ])->where('type', MoneyTransfer::officeCommission);

        return $this->applyDateFilter($query, $request->from, $request->to)
            ->latest()
            ->get();
    }

    /**
     * جلب سداد المديونية للسيارات (Payingcar)
     */
    private function getPayingCars(Request $request)
    {
        $query = Payingcar::with([
            'car',
            'user',
            'delivery_policy.booking_containers.booking',
            'delivery_policy.image'
        ]);

        return $this->applyDateFilter($query, $request->from, $request->to)
            ->latest()
            ->get();
    }

    /**
     * جلب سجلات النشاط المالية
     */
    private function getLogActivities(Request $request)
    {
        $query = LogActivity::with([
            'attacher',
            'log',
            'log.delivery_policy.booking_containers.booking',
            'log.delivery_policy.image'
        ])->whereIn('log_type', [
            AgentExpense::class,
            MoneyTransfer::class
        ]);

        $logActivities = $this->applyDateFilter($query, $request->from, $request->to)
            ->latest()
            ->get();

        // تحميل علاقة bookingContainer لسجلات AgentExpense
        foreach ($logActivities as $logActivity) {
            if ($logActivity->log_type === AgentExpense::class && $logActivity->log) {
                $logActivity->log->loadMissing('bookingContainer.booking');
            }
        }

        return $logActivities;
    }

    /**
     * تصفية LogActivity لإزالة السجلات المكررة
     */
    private function filterDuplicateLogActivities($logActivities, $agentExpenses, $deliveryPolicies, $officeCommissions, $payingCars = null)
    {
        // جمع IDs للسجلات المباشرة
        $existingIds = collect()
            ->merge($agentExpenses->map(fn($e) => ['type' => AgentExpense::class, 'id' => $e->id]))
            ->merge($deliveryPolicies->map(fn($t) => ['type' => MoneyTransfer::class, 'id' => $t->id]))
            ->merge($officeCommissions->map(fn($t) => ['type' => MoneyTransfer::class, 'id' => $t->id]));

        // إضافة Payingcar IDs إذا كانت موجودة
        if ($payingCars) {
            $existingIds = $existingIds->merge($payingCars->map(fn($p) => ['type' => Payingcar::class, 'id' => $p->id]));
        }

        // تصفية LogActivity
        return $logActivities->reject(function ($logActivity) use ($existingIds) {
            return $existingIds->contains(function ($existing) use ($logActivity) {
                return $existing['type'] === $logActivity->log_type &&
                       $existing['id'] == $logActivity->log_id;
            });
        });
    }

    /**
     * تطبيق البحث على البيانات
     */
    private function applySearch($expenses, $search)
    {
        $searchLower = mb_strtolower($search, 'UTF-8');

        return $expenses->filter(function ($item) use ($searchLower) {
            $data = $this->extractSearchableData($item);

            return mb_strpos(mb_strtolower($data['agentName'], 'UTF-8'), $searchLower) !== false ||
                   mb_strpos(mb_strtolower($data['bookingNumber'], 'UTF-8'), $searchLower) !== false ||
                   mb_strpos(mb_strtolower($data['service'], 'UTF-8'), $searchLower) !== false;
        });
    }

    /**
     * استخراج البيانات القابلة للبحث من السجل
     */
    private function extractSearchableData($item)
    {
        $data = ['agentName' => '', 'bookingNumber' => '', 'service' => ''];

        if ($item instanceof LogActivity) {
            $data['agentName'] = $item->attacher?->name ?? '';
            $log = $item->log;
            if ($log) {
                if ($item->log_type === AgentExpense::class && $log->bookingContainer) {
                    $data['bookingNumber'] = $log->bookingContainer->booking?->booking_number ?? '';
                } elseif ($item->log_type === MoneyTransfer::class && $log->delivery_policy) {
                    $firstContainer = $log->delivery_policy->booking_containers->first();
                    $data['bookingNumber'] = $firstContainer->booking?->booking_number ?? '';
                }
            }
        } elseif ($item instanceof MoneyTransfer) {
            $data['agentName'] = trim(($item->transferer?->name ?? '') . ' ' . ($item->transfered?->name ?? ''));
            if ($item->delivery_policy) {
                $firstContainer = $item->delivery_policy->booking_containers->first();
                $data['bookingNumber'] = $firstContainer->booking?->booking_number ?? '';
            }
        } elseif ($item instanceof AgentExpense) {
            $data['agentName'] = $item->agent?->name ?? '';
            $data['bookingNumber'] = $item->bookingContainer?->booking?->booking_number ?? '';
            if ($item->service) {
                $data['service'] = trim(($item->service->serviceCategory?->title ?? '') . ' ' . ($item->service->name ?? ''));
            }
        } elseif ($item instanceof Payingcar) {
            $data['agentName'] = $item->user?->name ?? '';
            if ($item->delivery_policy) {
                $firstContainer = $item->delivery_policy->booking_containers->first();
                $data['bookingNumber'] = $firstContainer->booking?->booking_number ?? '';
            }
            $data['service'] = 'سداد مديونية السيارة';
        }

        return $data;
    }
}

