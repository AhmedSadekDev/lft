<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Car;
use App\Models\InvoicePayment;
use App\Models\DeliveryPolicy;
use App\Models\Payingcar;
use App\Models\AgentExpense;
use App\Models\MoneyTransfer;
use App\Models\Vault;
use App\Models\VaultTransaction;
use App\Models\BankTrnsaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Exports\AccountStatementExport;
use App\Exports\CarStatementExport;
use App\Exports\FinancialPositionExport;
use App\Exports\ProfitLossReportExport;
use App\Models\BookingContrainerExtraCosts;
use App\Http\Traits\ImagesTrait;
use App\Models\Bank;
use App\Models\Booking;
use App\Models\BookingContainer;
use App\Models\Invoice;
use App\Services\InvoicePrintBuilder;
use App\Support\CompanyCurrencyLedger;
use App\Support\InvoiceCurrency;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;

class AccountController extends Controller
{
    use ImagesTrait;
    public function __construct()
    {
        // Clear permission cache to ensure new permissions are recognized
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->middleware('permission:accounts.index')->only(['index', 'statement', 'checksIndex', 'markCheckAsPaid']);
        $this->middleware('permission:accounts.create')->only(['showPaymentForm', 'processPayment', 'destroyPayment', 'destroyPaymentGroup']);
    }

    /**
     * عرض قائمة الحسابات
     */
    public function index(Request $request)
    {
        $companies = Company::query();

        // فلترة حسب اسم الشركة
        if ($request->filled('search')) {
            $companies->where('name', 'like', '%' . $request->search . '%');
        }

        $companies = $companies->orderBy('name')->paginate(20);

        return view('admin.accounts.index', compact('companies'));
    }

    /**
     * عرض كشف حساب لشركة معينة
     */
    public function statement(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);

        $fromDate = $request->from ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $toDate = $request->to ?? Carbon::now()->format('Y-m-d');
        $mode = $this->resolveStatementMode($request);

        $carriedForwardBalances = $this->calculateCarriedForwardBalances($company, $fromDate);
        // Backward-compatible scalar (EGP) for older export callers.
        $carriedForwardBalance = $carriedForwardBalances[InvoiceCurrency::EGP];

        $invoices = $this->getInvoicesInPeriod($company, $fromDate, $toDate);
        if (! is_a($invoices, \Illuminate\Support\Collection::class)) {
            $invoices = collect($invoices ?? []);
        }

        $totalInvoicesBalances = $this->sumInvoiceBuckets($invoices);
        $totalInvoices = $totalInvoicesBalances[InvoiceCurrency::EGP];

        $payments = $this->getPaymentsInPeriod($company, $fromDate, $toDate);
        if (! is_a($payments, \Illuminate\Support\Collection::class)) {
            $payments = collect($payments ?? []);
        }

        $totalPaymentsBalances = CompanyCurrencyLedger::paymentBuckets($payments);
        $totalPayments = $totalPaymentsBalances[InvoiceCurrency::EGP];

        $finalBalances = CompanyCurrencyLedger::sub(
            CompanyCurrencyLedger::add($carriedForwardBalances, $totalInvoicesBalances),
            $totalPaymentsBalances
        );
        $finalBalance = $finalBalances[InvoiceCurrency::EGP];

        $transactions = $this->buildTransactionsList(
            $invoices,
            $payments,
            $carriedForwardBalances,
            $fromDate,
            $company,
            $mode
        );

        return view('admin.accounts.statement', compact(
            'company',
            'fromDate',
            'toDate',
            'mode',
            'carriedForwardBalance',
            'carriedForwardBalances',
            'invoices',
            'totalInvoices',
            'totalInvoicesBalances',
            'totalPayments',
            'totalPaymentsBalances',
            'finalBalance',
            'finalBalances',
            'payments',
            'transactions'
        ));
    }

    /**
     * combined = فاتورة واحدة مجمّعة | detailed = أسطر I / R / S
     */
    private function resolveStatementMode(Request $request): string
    {
        $mode = strtolower((string) $request->get('mode', 'combined'));

        return in_array($mode, ['combined', 'detailed'], true) ? $mode : 'combined';
    }

    /**
     * بناء قائمة موحدة بجميع الحركات (فواتير + سداد)
     *
     * @param  array{EGP: float, USD: float}|float  $carriedForwardBalance
     * @param  string  $mode  combined|detailed
     */
    private function buildTransactionsList($invoices, $payments, $carriedForwardBalance, $fromDate = null, $company = null, string $mode = 'combined')
    {
        $transactions = collect();
        $printBuilder = new InvoicePrintBuilder();

        $carriedForwardBalances = is_array($carriedForwardBalance)
            ? $carriedForwardBalance
            : [InvoiceCurrency::EGP => (float) $carriedForwardBalance, InvoiceCurrency::USD => 0.0];

        $openingBalances = [
            InvoiceCurrency::EGP => (float) ($company->opening_balance ?? 0),
            InvoiceCurrency::USD => (float) ($company->opening_balance_usd ?? 0),
        ];
        $carriedForwardWithoutOpening = CompanyCurrencyLedger::sub($carriedForwardBalances, $openingBalances);
        $runningBalances = CompanyCurrencyLedger::zero();

        if ($openingBalances[InvoiceCurrency::EGP] != 0 || $openingBalances[InvoiceCurrency::USD] != 0) {
            $startDate = $fromDate ? Carbon::parse($fromDate) : Carbon::now()->startOfYear();
            $runningBalances = $openingBalances;
            $transactions->push([
                'date' => $startDate->copy()->subDay(),
                'type' => 'opening_balance',
                'type_label' => 'رصيد افتتاحي',
                'booking_number' => '',
                'invoice_number' => '',
                'currency' => $this->formatDualCurrencyLabel($openingBalances),
                'previous_debit' => 0,
                'previous_credit' => 0,
                'discount' => 0,
                'tax' => 0,
                'attachment_statement' => '',
                'transportation' => 0,
                'total' => $openingBalances[InvoiceCurrency::EGP],
                'total_egp' => $openingBalances[InvoiceCurrency::EGP],
                'total_usd' => $openingBalances[InvoiceCurrency::USD],
                'paid' => 0,
                'notes' => 'رصيد افتتاحي',
                'current_debit' => max(0, $openingBalances[InvoiceCurrency::EGP]) + max(0, $openingBalances[InvoiceCurrency::USD]),
                'current_credit' => abs(min(0, $openingBalances[InvoiceCurrency::EGP])) + abs(min(0, $openingBalances[InvoiceCurrency::USD])),
                'running_balance' => $runningBalances[InvoiceCurrency::EGP],
                'running_balance_egp' => $runningBalances[InvoiceCurrency::EGP],
                'running_balance_usd' => $runningBalances[InvoiceCurrency::USD],
            ]);
        }

        if ($carriedForwardWithoutOpening[InvoiceCurrency::EGP] != 0 || $carriedForwardWithoutOpening[InvoiceCurrency::USD] != 0) {
            $startDate = $fromDate ? Carbon::parse($fromDate) : Carbon::now()->startOfYear();
            $runningBalances = CompanyCurrencyLedger::add($runningBalances, $carriedForwardWithoutOpening);

            $transactions->push([
                'date' => $startDate->copy()->subDay(),
                'type' => 'carried_forward',
                'type_label' => 'رصيد مرحّل',
                'booking_number' => '',
                'invoice_number' => '',
                'currency' => $this->formatDualCurrencyLabel($carriedForwardWithoutOpening),
                'previous_debit' => 0,
                'previous_credit' => 0,
                'discount' => 0,
                'tax' => 0,
                'attachment_statement' => '',
                'transportation' => 0,
                'total' => $carriedForwardWithoutOpening[InvoiceCurrency::EGP],
                'total_egp' => $carriedForwardWithoutOpening[InvoiceCurrency::EGP],
                'total_usd' => $carriedForwardWithoutOpening[InvoiceCurrency::USD],
                'paid' => 0,
                'notes' => 'رصيد مرحّل من الفترة السابقة',
                'current_debit' => max(0, $carriedForwardWithoutOpening[InvoiceCurrency::EGP]) + max(0, $carriedForwardWithoutOpening[InvoiceCurrency::USD]),
                'current_credit' => abs(min(0, $carriedForwardWithoutOpening[InvoiceCurrency::EGP])) + abs(min(0, $carriedForwardWithoutOpening[InvoiceCurrency::USD])),
                'running_balance' => $runningBalances[InvoiceCurrency::EGP],
                'running_balance_egp' => $runningBalances[InvoiceCurrency::EGP],
                'running_balance_usd' => $runningBalances[InvoiceCurrency::USD],
            ]);
        }

        // إضافة الفواتير
        if ($invoices && is_iterable($invoices)) {
            foreach ($invoices as $invoice) {
                $invoiceId = is_array($invoice) ? ($invoice['id'] ?? null) : ($invoice->id ?? null);
                if (!$invoiceId) {
                    continue;
                }

                $invoiceObj = Invoice::with([
                    'booking.bookingContainers',
                    'booking.bookingServices.service.serviceCategory',
                    'booking.expenses.service.serviceCategory',
                ])->find($invoiceId);

                if (!$invoiceObj) {
                    continue;
                }

                $invoiceDate = is_array($invoice) ? ($invoice['date'] ?? now()) : ($invoice->date ?? now());
                $bookingNumber = is_array($invoice) ? ($invoice['booking_number'] ?? '') : ($invoice->booking_number ?? '');
                $invoiceNumber = is_array($invoice) ? ($invoice['invoice_number'] ?? '') : ($invoice->invoice_number ?? '');
                $legacyTotal = (float) (is_array($invoice) ? ($invoice['total'] ?? 0) : ($invoice->total ?? 0));

                $invoiceBuckets = CompanyCurrencyLedger::invoiceTotals(
                    $invoiceObj,
                    fn ($inv) => $this->calculateInvoiceTotal($inv)
                );

                if ($mode === 'detailed') {
                    $sectionRows = $this->buildDetailedInvoiceSectionRows(
                        $printBuilder,
                        $invoiceObj,
                        $invoiceDate,
                        $bookingNumber,
                        $invoiceNumber,
                        $legacyTotal
                    );

                    foreach ($sectionRows as $sectionRow) {
                        $sectionTotal = (float) ($sectionRow['total'] ?? 0);
                        $sectionBuckets = InvoiceCurrency::isUsd($invoiceObj->currency ?? null)
                            ? [InvoiceCurrency::EGP => 0.0, InvoiceCurrency::USD => $sectionTotal]
                            : [InvoiceCurrency::EGP => $sectionTotal, InvoiceCurrency::USD => 0.0];
                        $runningBalances = CompanyCurrencyLedger::add($runningBalances, $sectionBuckets);
                        $sectionRow['currency'] = $this->formatDualCurrencyLabel($sectionBuckets);
                        $sectionRow['total_egp'] = $sectionBuckets[InvoiceCurrency::EGP];
                        $sectionRow['total_usd'] = $sectionBuckets[InvoiceCurrency::USD];
                        $sectionRow['running_balance'] = $runningBalances[InvoiceCurrency::EGP];
                        $sectionRow['running_balance_egp'] = $runningBalances[InvoiceCurrency::EGP];
                        $sectionRow['running_balance_usd'] = $runningBalances[InvoiceCurrency::USD];
                        $transactions->push($sectionRow);
                    }

                    if (! InvoiceCurrency::isUsd($invoiceObj->currency ?? null)
                        && $invoiceBuckets[InvoiceCurrency::USD] > 0
                    ) {
                        $usdOnly = [InvoiceCurrency::EGP => 0.0, InvoiceCurrency::USD => $invoiceBuckets[InvoiceCurrency::USD]];
                        $runningBalances = CompanyCurrencyLedger::add($runningBalances, $usdOnly);
                        $transactions->push([
                            'date' => $invoiceDate,
                            'type' => 'invoice',
                            'type_label' => 'بنود دولار',
                            'booking_number' => $bookingNumber,
                            'invoice_number' => $invoiceNumber,
                            'currency' => InvoiceCurrency::label(InvoiceCurrency::USD),
                            'previous_debit' => 0,
                            'previous_credit' => 0,
                            'discount' => 0,
                            'tax' => 0,
                            'attachment_statement' => '',
                            'transportation' => 0,
                            'total' => $invoiceBuckets[InvoiceCurrency::USD],
                            'total_egp' => 0,
                            'total_usd' => $invoiceBuckets[InvoiceCurrency::USD],
                            'paid' => 0,
                            'notes' => 'إيصالات/خدمات بالدولار',
                            'current_debit' => $invoiceBuckets[InvoiceCurrency::USD],
                            'current_credit' => 0,
                            'running_balance' => $runningBalances[InvoiceCurrency::EGP],
                            'running_balance_egp' => $runningBalances[InvoiceCurrency::EGP],
                            'running_balance_usd' => $runningBalances[InvoiceCurrency::USD],
                            'section' => 'usd',
                            'line_order' => 99,
                        ]);
                    }
                } else {
                    $transportationTotal = $invoiceObj->transportation_total_before_vat ?? 0;
                    $vatAmount = $invoiceObj->value_added_tax_amount ?? 0;
                    $discountAmount = $invoiceObj->discount_amount ?? 0;
                    $runningBalances = CompanyCurrencyLedger::add($runningBalances, $invoiceBuckets);

                    $transactions->push([
                        'date' => $invoiceDate,
                        'type' => 'invoice',
                        'type_label' => 'فاتورة',
                        'booking_number' => $bookingNumber,
                        'invoice_number' => $invoiceNumber,
                        'currency' => $this->formatDualCurrencyLabel($invoiceBuckets),
                        'previous_debit' => 0,
                        'previous_credit' => 0,
                        'discount' => $discountAmount,
                        'tax' => $vatAmount,
                        'attachment_statement' => '',
                        'transportation' => $transportationTotal,
                        'total' => $invoiceBuckets[InvoiceCurrency::EGP] ?: $invoiceBuckets[InvoiceCurrency::USD],
                        'total_egp' => $invoiceBuckets[InvoiceCurrency::EGP],
                        'total_usd' => $invoiceBuckets[InvoiceCurrency::USD],
                        'paid' => 0,
                        'notes' => '',
                        'current_debit' => $invoiceBuckets[InvoiceCurrency::EGP] + $invoiceBuckets[InvoiceCurrency::USD],
                        'current_credit' => 0,
                        'running_balance' => $runningBalances[InvoiceCurrency::EGP],
                        'running_balance_egp' => $runningBalances[InvoiceCurrency::EGP],
                        'running_balance_usd' => $runningBalances[InvoiceCurrency::USD],
                        'section' => 'combined',
                    ]);
                }
            }
        }

        // تجميع المدفوعات حسب التاريخ + العملة
        $groupedPayments = collect();
        if ($payments && $payments->count() > 0) {
            $groupedPayments = $payments->groupBy(function ($payment) {
                $currency = InvoiceCurrency::normalize($payment->currency ?? InvoiceCurrency::EGP);

                return Carbon::parse($payment->created_at)->format('Y-m-d') . '|' . $currency;
            });
        }

        foreach ($groupedPayments as $dateCurrency => $dayPayments) {
            [$date, $payCurrency] = array_pad(explode('|', (string) $dateCurrency, 2), 2, InvoiceCurrency::EGP);
            $payCurrency = InvoiceCurrency::normalize($payCurrency);
            $paymentBuckets = CompanyCurrencyLedger::paymentBuckets($dayPayments);
            $totalPaymentValue = $paymentBuckets[$payCurrency];
            $runningBalances = CompanyCurrencyLedger::sub($runningBalances, $paymentBuckets);

            $paymentDetails = $dayPayments->map(function ($payment) {
                $notes = '';
                if ($payment->notes && strpos(ltrim($payment->notes), '{') !== 0) {
                    $notes = $payment->notes;
                } elseif ($payment->bank_id) {
                    $bank = Bank::find($payment->bank_id);
                    $notes = $bank ? ('تحويل ' . $bank->name) : 'سداد';
                } else {
                    $notes = 'قام العميل بسداد';
                }

                $invNo = $payment->invoice ? ($payment->invoice->invoice_number ?? '') : '';
                $bookNo = $payment->invoice && $payment->invoice->booking
                    ? ($payment->invoice->booking->booking_number ?? '')
                    : '';

                return [
                    'id' => $payment->id,
                    'invoice_number' => $invNo !== '' ? $invNo : ($payment->payment_type === 'check' && $payment->check_number ? ('شيك ' . $payment->check_number) : ''),
                    'booking_number' => $bookNo,
                    'value' => floatval($payment->value),
                    'currency' => InvoiceCurrency::normalize($payment->currency ?? InvoiceCurrency::EGP),
                    'notes' => $notes,
                    'payment_type' => $payment->payment_type ?? 'bank_transfer',
                    'bank_name' => $payment->bank ? $payment->bank->name : ($payment->check_bank_name ?? ''),
                    'check_number' => $payment->check_number ?? '',
                    'date' => $payment->created_at,
                ];
            })->values()->toArray();

            $firstPayment = $dayPayments->first();
            $notes = '';
            if ($firstPayment->notes) {
                $notes = $firstPayment->notes;
            } elseif ($firstPayment->bank_id) {
                $bank = Bank::find($firstPayment->bank_id);
                $notes = $bank ? ('تحويل ' . $bank->name) : 'سداد';
            } else {
                $notes = 'قام العميل بسداد';
            }

            $firstInv = $firstPayment->invoice;
            $firstInvNo = $firstInv ? ($firstInv->invoice_number ?? '') : '';
            if ($firstInvNo === '' && $firstPayment->payment_type === 'check' && $firstPayment->check_number) {
                $firstInvNo = 'شيك ' . $firstPayment->check_number;
            }

            $transactions->push([
                'date' => Carbon::parse($date),
                'type' => 'payment',
                'type_label' => 'قام العميل بسداد',
                'booking_number' => '',
                'invoice_number' => $dayPayments->count() > 1 ? 'متعدد (' . $dayPayments->count() . ' فاتورة)' : $firstInvNo,
                'currency' => InvoiceCurrency::label($payCurrency),
                'previous_debit' => 0,
                'previous_credit' => 0,
                'discount' => 0,
                'tax' => 0,
                'attachment_statement' => '',
                'transportation' => 0,
                'total' => 0,
                'total_egp' => $paymentBuckets[InvoiceCurrency::EGP],
                'total_usd' => $paymentBuckets[InvoiceCurrency::USD],
                'paid' => $totalPaymentValue,
                'notes' => $notes,
                'current_debit' => 0,
                'current_credit' => $totalPaymentValue,
                'running_balance' => $runningBalances[InvoiceCurrency::EGP],
                'running_balance_egp' => $runningBalances[InvoiceCurrency::EGP],
                'running_balance_usd' => $runningBalances[InvoiceCurrency::USD],
                'payment_details' => $paymentDetails,
                'payment_count' => $dayPayments->count(),
            ]);
        }

        $sortedTransactions = $transactions->sortBy([
            ['date', 'asc'],
            ['line_order', 'asc'],
        ])->values();

        $sortedTransactions = $sortedTransactions->map(function ($transaction, $index) use ($sortedTransactions) {
            if ($index > 0) {
                $previousDebit = $sortedTransactions->take($index)->sum('current_debit');
                $previousCredit = $sortedTransactions->take($index)->sum('current_credit');
                $transaction['previous_debit'] = $previousDebit;
                $transaction['previous_credit'] = $previousCredit;
            }

            return $transaction;
        });

        // عرض تنازلي مع الإبقاء على ترتيب I ثم R ثم S داخل نفس التاريخ
        return $sortedTransactions->sortBy([
            ['date', 'desc'],
            ['line_order', 'asc'],
        ])->values();
    }

    /**
     * أسطر الفاتورة التفصيلية: ضريبية (I) ثم إيصالات (R) ثم خدمات إضافية (S)
     */
    private function buildDetailedInvoiceSectionRows(
        InvoicePrintBuilder $printBuilder,
        Invoice $invoiceObj,
        $invoiceDate,
        string $bookingNumber,
        string $invoiceNumber,
        float $legacyTotal
    ): array {
        $printData = $printBuilder->build($invoiceObj);
        $rows = [];

        $sections = [
            [
                'key' => 'tax',
                'group' => $printData['tax'],
                'type' => 'invoice_tax',
                'type_label' => 'فاتورة ضريبية (I)',
                'include_tax_meta' => true,
            ],
            [
                'key' => 'receipt',
                'group' => $printData['receipt'],
                'type' => 'invoice_receipt',
                'type_label' => 'إيصالات (R)',
                'include_tax_meta' => false,
            ],
            [
                'key' => 'additional',
                'group' => $printData['additional'],
                'type' => 'invoice_additional',
                'type_label' => 'خدمات إضافية (S)',
                'include_tax_meta' => false,
            ],
        ];

        $sectionSum = collect($sections)->sum(fn ($s) => (float) ($s['group']['total'] ?? 0));
        // إن اختلف مجموع الأقسام عن إجمالي كشف الحساب المحفوظ، نضبط الفرق على قسم الضريبة للحفاظ على الرصيد
        $diff = round($legacyTotal - $sectionSum, 2);

        foreach ($sections as $index => $section) {
            $amount = (float) ($section['group']['total'] ?? 0);
            if ($index === 0 && abs($diff) > 0.009) {
                $amount += $diff;
            }

            if ($amount <= 0 && ($section['group']['items'] ?? collect())->isEmpty()) {
                continue;
            }
            if ($amount == 0.0) {
                continue;
            }

            $rows[] = [
                'date' => $invoiceDate,
                'type' => $section['type'],
                'type_label' => $section['type_label'],
                'booking_number' => $bookingNumber,
                'invoice_number' => $section['group']['number'] ?? ($invoiceNumber . '-' . ($section['group']['suffix'] ?? '')),
                'previous_debit' => 0,
                'previous_credit' => 0,
                'discount' => $section['include_tax_meta'] ? (float) ($section['group']['discount'] ?? 0) : 0,
                'tax' => $section['include_tax_meta'] ? (float) ($section['group']['vat'] ?? 0) : 0,
                'attachment_statement' => $section['key'] === 'receipt' ? 'إيصالات' : ($section['key'] === 'additional' ? 'خدمات إضافية' : ''),
                'transportation' => $section['key'] === 'tax' ? (float) ($invoiceObj->transportation_total_before_vat ?? 0) : 0,
                'total' => $amount,
                'paid' => 0,
                'notes' => $section['group']['label'] ?? '',
                'current_debit' => $amount,
                'current_credit' => 0,
                'section' => $section['key'],
                'line_order' => $index + 1,
            ];
        }

        // لو كل الأقسام فارغة لأي سبب، أبقِ سطراً واحداً بالمبلغ القديم
        if (empty($rows) && $legacyTotal > 0) {
            $rows[] = [
                'date' => $invoiceDate,
                'type' => 'invoice',
                'type_label' => 'فاتورة',
                'booking_number' => $bookingNumber,
                'invoice_number' => $invoiceNumber,
                'previous_debit' => 0,
                'previous_credit' => 0,
                'discount' => (float) ($invoiceObj->discount_amount ?? 0),
                'tax' => (float) ($invoiceObj->value_added_tax_amount ?? 0),
                'attachment_statement' => '',
                'transportation' => (float) ($invoiceObj->transportation_total_before_vat ?? 0),
                'total' => $legacyTotal,
                'paid' => 0,
                'notes' => '',
                'current_debit' => $legacyTotal,
                'current_credit' => 0,
                'section' => 'combined',
            ];
        }

        return $rows;
    }

    /**
     * تصدير كشف الحساب إلى Excel
     */
    public function exportExcel(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);

        $fromDate = $request->from ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $toDate = $request->to ?? Carbon::now()->format('Y-m-d');
        $mode = $this->resolveStatementMode($request);

        $carriedForwardBalances = $this->calculateCarriedForwardBalances($company, $fromDate);
        $carriedForwardBalance = $carriedForwardBalances[InvoiceCurrency::EGP];
        $invoices = $this->getInvoicesInPeriod($company, $fromDate, $toDate);
        $totalInvoicesBalances = $this->sumInvoiceBuckets($invoices);
        $totalInvoices = $totalInvoicesBalances[InvoiceCurrency::EGP];
        $payments = $this->getPaymentsInPeriod($company, $fromDate, $toDate);
        $totalPaymentsBalances = CompanyCurrencyLedger::paymentBuckets($payments);
        $totalPayments = $totalPaymentsBalances[InvoiceCurrency::EGP];
        $finalBalances = CompanyCurrencyLedger::sub(
            CompanyCurrencyLedger::add($carriedForwardBalances, $totalInvoicesBalances),
            $totalPaymentsBalances
        );
        $finalBalance = $finalBalances[InvoiceCurrency::EGP];
        $transactions = $this->buildTransactionsList($invoices, $payments, $carriedForwardBalances, $fromDate, $company, $mode);

        // ترتيب تصاعدي (ASC) للتصدير مع الحفاظ على I ثم R ثم S
        $transactions = $transactions->sortBy([
            ['date', 'asc'],
            ['line_order', 'asc'],
        ])->values();

        $modeLabel = $mode === 'detailed' ? 'تفصيلي' : 'مجمع';
        $fileName = 'كشف_حساب_' . $modeLabel . '_' . $company->name . '_' . $fromDate . '_' . $toDate . '.xlsx';

        return Excel::download(
            new AccountStatementExport(
                $company,
                $fromDate,
                $toDate,
                $carriedForwardBalances,
                $totalInvoicesBalances,
                $totalPaymentsBalances,
                $finalBalances,
                $invoices,
                $payments,
                $transactions ?? collect(),
                $mode
            ),
            $fileName
        );
    }

    /**
     * تصدير كشف الحساب إلى PDF
     */
    public function exportPDF(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);

        $fromDate = $request->from ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $toDate = $request->to ?? Carbon::now()->format('Y-m-d');
        $mode = $this->resolveStatementMode($request);

        $carriedForwardBalances = $this->calculateCarriedForwardBalances($company, $fromDate);
        $carriedForwardBalance = $carriedForwardBalances[InvoiceCurrency::EGP];
        $invoices = $this->getInvoicesInPeriod($company, $fromDate, $toDate);
        $totalInvoicesBalances = $this->sumInvoiceBuckets($invoices);
        $totalInvoices = $totalInvoicesBalances[InvoiceCurrency::EGP];
        $payments = $this->getPaymentsInPeriod($company, $fromDate, $toDate);
        $totalPaymentsBalances = CompanyCurrencyLedger::paymentBuckets($payments);
        $totalPayments = $totalPaymentsBalances[InvoiceCurrency::EGP];
        $finalBalances = CompanyCurrencyLedger::sub(
            CompanyCurrencyLedger::add($carriedForwardBalances, $totalInvoicesBalances),
            $totalPaymentsBalances
        );
        $finalBalance = $finalBalances[InvoiceCurrency::EGP];
        $transactions = $this->buildTransactionsList($invoices, $payments, $carriedForwardBalances, $fromDate, $company, $mode);

        // ترتيب تصاعدي (ASC) للتصدير
        $transactions = $transactions->sortBy([
            ['date', 'asc'],
            ['line_order', 'asc'],
        ])->values();

        $html = view('admin.accounts.statement-pdf', compact(
            'company',
            'fromDate',
            'toDate',
            'carriedForwardBalances',
            'totalInvoicesBalances',
            'totalPaymentsBalances',
            'finalBalances',
            'mode',
            'carriedForwardBalance',
            'invoices',
            'totalInvoices',
            'totalPayments',
            'finalBalance',
            'payments',
            'transactions'
        ))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
            'margin_left' => 6,
            'margin_right' => 6,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'margin_header' => 4,
            'margin_footer' => 4,
        ]);

        $mpdf->WriteHTML($html);

        $fileName = 'كشف_حساب_' . $company->name . '_' . $fromDate . '_' . $toDate . '.pdf';

        return $mpdf->Output($fileName, 'D');
    }

    /**
     * معاينة وطباعة بيان سداد لمجموعة دفعات (من كشف حساب الشركة)
     */
    public function companyStatementPaymentReceiptPrint(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);
        $payments = $this->resolveCompanyStatementPaymentsFromIds((int) $companyId, (string) $request->query('payment_ids', ''));
        [$rows, $totalAmount] = $this->buildCompanyPaymentReceiptRows($payments);
        $showPrintChrome = true;

        return view('admin.accounts.company-payment-receipt', compact('company', 'rows', 'totalAmount', 'showPrintChrome'));
    }

    /**
     * تحميل بيان سداد لمجموعة دفعات كـ PDF
     */
    public function companyStatementPaymentReceiptPdf(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);
        $payments = $this->resolveCompanyStatementPaymentsFromIds((int) $companyId, (string) $request->query('payment_ids', ''));
        [$rows, $totalAmount] = $this->buildCompanyPaymentReceiptRows($payments);

        $html = view('admin.accounts.company-payment-receipt', compact('company', 'rows', 'totalAmount'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
            'margin_left' => 6,
            'margin_right' => 6,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'margin_header' => 4,
            'margin_footer' => 4,
        ]);

        $mpdf->WriteHTML($html);

        $fileName = 'بيان_سداد_' . $company->id . '_' . date('Y-m-d') . '.pdf';

        return $mpdf->Output($fileName, 'D');
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\InvoicePayment>
     */
    private function resolveCompanyStatementPaymentsFromIds(int $companyId, string $paymentIdsRaw)
    {
        $ids = collect(explode(',', $paymentIdsRaw))
            ->map(fn ($v) => (int) trim((string) $v))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty() || $ids->count() > 200) {
            abort(404);
        }

        $payments = InvoicePayment::whereIn('id', $ids)
            ->with(['invoice.booking', 'bank'])
            ->orderBy('id')
            ->get();

        if ($payments->count() !== $ids->count()) {
            abort(404);
        }

        foreach ($payments as $payment) {
            if (!$payment->invoice || !$payment->invoice->booking) {
                abort(404);
            }
            if ((int) $payment->invoice->booking->company_id !== $companyId) {
                abort(404);
            }
        }

        return $payments;
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: float}
     */
    private function buildCompanyPaymentReceiptRows($payments): array
    {
        $rows = [];
        $total = 0.0;

        foreach ($payments as $payment) {
            $notes = '';
            if ($payment->notes) {
                $notes = $payment->notes;
            } elseif ($payment->bank_id) {
                $bank = $payment->bank ?? Bank::find($payment->bank_id);
                $notes = $bank ? 'تحويل ' . $bank->name : 'سداد';
            } else {
                $notes = 'قام العميل بسداد';
            }

            $val = (float) $payment->value;
            $total += $val;

            $rows[] = [
                'invoice_number' => $payment->invoice->invoice_number ?? '',
                'booking_number' => $payment->invoice->booking->booking_number ?? '',
                'value' => $val,
                'payment_type' => $payment->payment_type ?? 'bank_transfer',
                'bank_name' => $payment->bank ? $payment->bank->name : ($payment->check_bank_name ?? ''),
                'notes' => $notes,
                'date' => $payment->created_at,
            ];
        }

        return [$rows, $total];
    }

    /**
     * عرض صفحة السداد
     */
    public function showPaymentForm($companyId)
    {
        $company = Company::findOrFail($companyId);
        $banks = Bank::orderBy('name')->get();

        $currentBalances = $this->calculateCurrentBalances($company);
        $currentBalance = $currentBalances[InvoiceCurrency::EGP];

        $unpaidInvoices = collect();
        $bookings = $company->bookings()
            ->whereHas('invoice')
            ->with(['invoice.invoicePayments'])
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($bookings as $booking) {
            $invoice = $booking->invoice;
            if (! $invoice) {
                continue;
            }

            $remaining = CompanyCurrencyLedger::invoiceRemaining(
                $invoice,
                fn ($inv) => $this->calculateInvoiceTotal($inv)
            );

            if ($remaining[InvoiceCurrency::EGP] > 0 || $remaining[InvoiceCurrency::USD] > 0) {
                $totals = CompanyCurrencyLedger::invoiceTotals($invoice, fn ($inv) => $this->calculateInvoiceTotal($inv));
                $paid = CompanyCurrencyLedger::invoicePaid($invoice);
                $unpaidInvoices->push([
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'booking_number' => $booking->booking_number ?? '-',
                    'date' => $invoice->created_at,
                    'currency' => InvoiceCurrency::normalize($invoice->currency ?? InvoiceCurrency::EGP),
                    'total' => $totals[InvoiceCurrency::EGP] ?: $totals[InvoiceCurrency::USD],
                    'total_egp' => $totals[InvoiceCurrency::EGP],
                    'total_usd' => $totals[InvoiceCurrency::USD],
                    'paid' => $paid[InvoiceCurrency::EGP] + $paid[InvoiceCurrency::USD],
                    'paid_egp' => $paid[InvoiceCurrency::EGP],
                    'paid_usd' => $paid[InvoiceCurrency::USD],
                    'remaining' => $remaining[InvoiceCurrency::EGP] + $remaining[InvoiceCurrency::USD],
                    'remaining_egp' => $remaining[InvoiceCurrency::EGP],
                    'remaining_usd' => $remaining[InvoiceCurrency::USD],
                ]);
            }
        }

        return view('admin.accounts.payment', compact(
            'company',
            'banks',
            'currentBalance',
            'currentBalances',
            'unpaidInvoices'
        ));
    }

    /**
     * معالجة السداد
     */
    public function processPayment(Request $request, $companyId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:EGP,USD',
            'payment_date' => 'required|date',
            'payment_type' => 'required|in:bank_transfer,check',
            'payment_target' => 'required|in:invoices,opening_balance',
            'bank_id' => 'required_if:payment_type,bank_transfer,check|nullable|exists:banks,id',
            'check_bank_name' => 'required_if:payment_type,check|nullable|string|max:255',
            'check_number' => 'required_if:payment_type,check|nullable|string|max:255',
            'check_value' => 'required_if:payment_type,check|nullable|numeric|min:0.01',
            'check_due_date' => 'required_if:payment_type,check|nullable|date',
            'notes' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'invoice_ids' => 'nullable|string',
        ]);

        $company = Company::findOrFail($companyId);
        $currency = InvoiceCurrency::normalize($request->currency);
        $currencyLabel = InvoiceCurrency::label($currency);
        $paymentAmount = $request->payment_type === 'check' ? (float) $request->check_value : (float) $request->amount;

        DB::beginTransaction();

        try {
            if ($request->payment_target === 'opening_balance') {
                $openingField = $currency === InvoiceCurrency::USD ? 'opening_balance_usd' : 'opening_balance';
                $openingBalance = (float) ($company->{$openingField} ?? 0);

                if ($openingBalance <= 0) {
                    DB::rollBack();

                    return redirect()->back()->with('error', 'لا يوجد رصيد افتتاحي (' . $currencyLabel . ') لسداده');
                }

                if ($paymentAmount > $openingBalance) {
                    DB::rollBack();

                    return redirect()->back()->with(
                        'error',
                        'المبلغ أكبر من الرصيد الافتتاحي. الرصيد الافتتاحي: '
                            . number_format($openingBalance, 2) . ' ' . $currencyLabel
                    );
                }

                $company->{$openingField} = $openingBalance - $paymentAmount;
                $company->save();

                if ($request->bank_id && $request->payment_type !== 'check') {
                    $this->recordCompanyBankDeposit(
                        (int) $request->bank_id,
                        $company,
                        $paymentAmount,
                        $currency,
                        'سداد الرصيد الافتتاحي - ' . $company->name,
                        $request->payment_date ?? now()->format('Y-m-d')
                    );
                }

                DB::commit();

                return redirect()->route('accounts.statement', $companyId)
                    ->with('success', 'تم سداد الرصيد الافتتاحي بنجاح. المبلغ: ' . number_format($paymentAmount, 2) . ' ' . $currencyLabel);
            }

            if ($request->payment_type === 'check') {
                $paymentData = $this->prepareCompanyCheckPaymentData($request, $company, $paymentAmount);
                $paymentData['currency'] = $currency;
                $payment = InvoicePayment::create($paymentData);
                if ($request->payment_date) {
                    $payment->created_at = $request->payment_date;
                    $payment->save();
                }

                DB::commit();

                return redirect()->route('accounts.statement', $companyId)
                    ->with('success', 'تم تسجيل الشيك بقيمته الإجمالية. سيُضاف المبلغ للحساب البنكي عند الضغط على «تم الاستحقاق» من صفحة الشيكات.');
            }

            $remainingPayment = $paymentAmount;
            $processedInvoices = [];
            $invoiceCount = 0;
            $bankTransactionId = null;

            if ($request->bank_id) {
                $bankTransactionId = $this->recordCompanyBankDeposit(
                    (int) $request->bank_id,
                    $company,
                    $paymentAmount,
                    $currency,
                    'سداد فواتير - ' . $company->name,
                    $request->payment_date ?? now()->format('Y-m-d')
                );
            }

            $applyToInvoice = function (Invoice $invoice) use (
                &$remainingPayment,
                &$processedInvoices,
                &$invoiceCount,
                $request,
                $company,
                $bankTransactionId,
                $currency
            ) {
                if ($remainingPayment <= 0) {
                    return;
                }
                $remaining = CompanyCurrencyLedger::invoiceRemaining(
                    $invoice,
                    fn ($inv) => $this->calculateInvoiceTotal($inv)
                );
                $remainingAmount = $remaining[$currency] ?? 0;
                if ($remainingAmount <= 0) {
                    return;
                }

                $paymentValue = min($remainingAmount, $remainingPayment);
                $paymentData = $this->preparePaymentData($request, $company, $invoice, $paymentValue, $bankTransactionId);
                $paymentData['currency'] = $currency;
                $payment = InvoicePayment::create($paymentData);
                if ($request->payment_date) {
                    $payment->created_at = $request->payment_date;
                    $payment->save();
                }
                $processedInvoices[] = $invoice->invoice_number;
                $invoiceCount++;
                $remainingPayment -= $paymentValue;
            };

            if ($request->invoice_ids) {
                $invoiceIds = array_filter(explode(',', $request->invoice_ids));
                if (count($invoiceIds) > 0) {
                    $invoices = Invoice::whereIn('id', $invoiceIds)
                        ->whereHas('booking', function ($query) use ($companyId) {
                            $query->where('company_id', $companyId);
                        })
                        ->with(['booking', 'invoicePayments'])
                        ->orderBy('created_at', 'asc')
                        ->get();

                    foreach ($invoices as $invoice) {
                        $applyToInvoice($invoice);
                    }
                }
            } else {
                $bookings = $company->bookings()
                    ->whereHas('invoice')
                    ->with(['invoice.invoicePayments'])
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($bookings as $booking) {
                    if ($remainingPayment <= 0) {
                        break;
                    }
                    if ($booking->invoice) {
                        $applyToInvoice($booking->invoice);
                    }
                }
            }

            if ($invoiceCount === 0) {
                DB::rollBack();

                return redirect()->back()->with(
                    'error',
                    'لا يوجد مستحق بنفس العملة (' . $currencyLabel . ') لتوزيع السداد عليه'
                );
            }

            DB::commit();

            $message = 'تم تسجيل السداد بنجاح (' . $currencyLabel . ')';
            if ($invoiceCount > 0) {
                $message .= ' (' . $invoiceCount . ' فاتورة: ' . implode(', ', $processedInvoices) . ')';
            }

            return redirect()->route('accounts.statement', $companyId)
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'حدث خطأ أثناء تسجيل السداد: ' . $e->getMessage());
        }
    }

    /**
     * EGP deposits update banks.amount; USD deposits are tagged only (no mix into EGP bank book).
     */
    private function recordCompanyBankDeposit(
        int $bankId,
        Company $company,
        float $amount,
        string $currency,
        string $name,
        string $date
    ): ?int {
        $bank = Bank::findOrFail($bankId);
        $currency = InvoiceCurrency::normalize($currency);

        if ($currency === InvoiceCurrency::EGP) {
            $bank->amount = ($bank->amount ?? 0) + $amount;
            $bank->save();
        }

        $bankTransaction = BankTrnsaction::create([
            'bank_id' => $bank->id,
            'company_id' => $company->id,
            'user_id' => auth()->id(),
            'name' => $name . ($currency === InvoiceCurrency::USD ? ' (USD)' : ''),
            'type' => 1,
            'amount' => $amount,
            'currency' => $currency,
            'date' => $date,
        ]);

        return $bankTransaction->id;
    }

    /**
     * حذف سداد مسجل وإرجاعه كمبلغ مستحق مرة أخرى
     */
    public function destroyPayment(Request $request, $companyId, $paymentId)
    {
        $company = Company::findOrFail($companyId);

        $payment = InvoicePayment::with(['invoice.booking', 'bank'])
            ->where('id', $paymentId)
            ->where(function ($q) use ($companyId) {
                $q->whereHas('invoice.booking', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })->orWhere(function ($q2) use ($companyId) {
                    $q2->whereNull('invoice_id')->where('company_id', $companyId);
                });
            })
            ->firstOrFail();

        DB::beginTransaction();

        try {
            // عند حذف سداد من كشف الحساب: خصم المبلغ من البنك (EGP فقط) وحذف/تحديث عملية البنك المرتبطة
            if ($payment->bank_id) {
                $bank = $payment->bank;

                if ($bank && ! InvoiceCurrency::isUsd($payment->currency ?? null)) {
                    $bank->amount = ($bank->amount ?? 0) - (float) $payment->value;
                    $bank->save();
                }

                // تعديل/حذف العملية البنكية الأصلية المرتبطة بالسداد
                if (!is_null($payment->bank_transaction_id)) {
                    $bankTransaction = BankTrnsaction::find($payment->bank_transaction_id);
                    if ($bankTransaction) {
                        $newAmount = ((float) $bankTransaction->amount) - ((float) $payment->value);
                        if ($newAmount <= 0) {
                            $bankTransaction->delete();
                        } else {
                            $bankTransaction->amount = $newAmount;
                            $bankTransaction->save();
                        }
                    }
                }
            }

            $payment->delete();

            DB::commit();

            return redirect()->route('accounts.statement', [
                'companyId' => $company->id,
                'from' => $request->get('from'),
                'to' => $request->get('to'),
            ])->with('success', 'تم حذف عملية السداد بنجاح، وتم إعادة المبلغ كمستحق على الشركة');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ أثناء حذف عملية السداد: ' . $e->getMessage());
        }
    }

    /**
     * حذف عملية سداد كاملة (مجموعة دفعات) وإرجاعها كمبالغ مستحقة
     */
    public function destroyPaymentGroup(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);

        $paymentIdsRaw = $request->input('payment_ids', '');
        $paymentIds = collect(explode(',', (string) $paymentIdsRaw))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($paymentIds->isEmpty()) {
            return redirect()->back()->with('error', 'لا توجد عمليات سداد صالحة للحذف');
        }

        $payments = InvoicePayment::with(['invoice.booking', 'bank'])
            ->whereIn('id', $paymentIds)
            ->where(function ($q) use ($companyId) {
                $q->whereHas('invoice.booking', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })->orWhere(function ($q2) use ($companyId) {
                    $q2->whereNull('invoice_id')->where('company_id', $companyId);
                });
            })
            ->get();

        if ($payments->isEmpty()) {
            return redirect()->back()->with('error', 'لم يتم العثور على عمليات السداد المطلوبة');
        }

        DB::beginTransaction();

        try {
            $bankTransactionDeductions = [];

            foreach ($payments as $payment) {
                // عند حذف السداد من كشف الحساب: خصم من البنك
                if ($payment->bank_id) {
                    $bank = $payment->bank;

                    if ($bank) {
                        $bank->amount = ($bank->amount ?? 0) - (float) $payment->value;
                        $bank->save();
                    }

                    if (!is_null($payment->bank_transaction_id)) {
                        if (!isset($bankTransactionDeductions[$payment->bank_transaction_id])) {
                            $bankTransactionDeductions[$payment->bank_transaction_id] = 0.0;
                        }
                        $bankTransactionDeductions[$payment->bank_transaction_id] += (float) $payment->value;
                    }
                }

                $payment->delete();
            }

            // تعديل/حذف سجلات البنك المرتبطة بنفس عمليات السداد المحذوفة
            foreach ($bankTransactionDeductions as $bankTransactionId => $deductedAmount) {
                $bankTransaction = BankTrnsaction::find($bankTransactionId);
                if (!$bankTransaction) {
                    continue;
                }

                $newAmount = ((float) $bankTransaction->amount) - (float) $deductedAmount;
                if ($newAmount <= 0) {
                    $bankTransaction->delete();
                } else {
                    $bankTransaction->amount = $newAmount;
                    $bankTransaction->save();
                }
            }

            DB::commit();

            return redirect()->route('accounts.statement', [
                'companyId' => $company->id,
                'from' => $request->get('from'),
                'to' => $request->get('to'),
            ])->with('success', 'تم حذف عملية السداد بالكامل وإعادة المبالغ كمستحق على الشركة');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ أثناء حذف عملية السداد: ' . $e->getMessage());
        }
    }

    /**
     * إعداد بيانات السداد
     */
    private function preparePaymentData($request, $company, $invoice, $paymentValue, $bankTransactionId = null)
    {
        $paymentData = [
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'value' => $paymentValue,
            'currency' => InvoiceCurrency::normalize($request->currency ?? InvoiceCurrency::EGP),
            'user_id' => auth()->id(),
            'payment_type' => $request->payment_type,
        ];

        // إضافة بيانات البنك للتحويل البنكي والشيك
        if (in_array($request->payment_type, ['bank_transfer', 'check'])) {
            $paymentData['bank_id'] = $request->bank_id;
        }

        if (!is_null($bankTransactionId)) {
            $paymentData['bank_transaction_id'] = $bankTransactionId;
        }

        // إضافة بيانات الشيك
        if ($request->payment_type === 'check') {
            $paymentData['check_bank_name'] = $request->check_bank_name;
            $paymentData['check_number'] = $request->check_number;
            $paymentData['check_due_date'] = $request->check_due_date;
        }

        // رفع الصورة إذا كانت موجودة
        if ($request->hasFile('image')) {
            $imageName = time() . '_payment.' . $request->image->extension();
            $imagePath = $request->image->storeAs('invoice_payments', $imageName, 'public');
            $paymentData['image'] = "storage/" . $imagePath;
        } else {
            $paymentData['image'] = '';
        }

        // إضافة الملاحظات
        if ($request->notes) {
            $paymentData['notes'] = $request->notes;
        }

        return $paymentData;
    }

    /**
     * عرض صفحة الشيكات
     */
    public function checksIndex(Request $request)
    {
        $query = InvoicePayment::where('payment_type', 'check')
            ->whereNotNull('check_due_date')
            ->with(['invoice.booking.company', 'company']);

        // البحث برقم الشيك
        if ($request->filled('search')) {
            $query->where('check_number', 'like', '%' . $request->search . '%');
        }

        // ترتيب حسب تاريخ الاستحقاق (الأولوية)
        $checks = $query->orderBy('check_due_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return view('admin.accounts.checks', compact('checks'));
    }

    /**
     * تم الاستحقاق: إضافة المبلغ للحساب البنكي وتحويل الشيك إلى سدادات فعلية على الفواتير (بدون تكرار).
     */
    public function markCheckAsPaid($paymentId)
    {
        $payment = InvoicePayment::with(['company', 'invoice.booking'])->findOrFail($paymentId);

        if ($payment->payment_type !== 'check') {
            return redirect()->back()->with('error', 'هذا السجل ليس شيك');
        }

        if ($payment->check_paid_at) {
            return redirect()->back()->with('error', 'تم استحقاق هذا الشيك مسبقاً');
        }

        DB::beginTransaction();

        try {
            $linkedToInvoice = (bool) $payment->invoice_id;
            $company = $payment->company;
            if (!$company) {
                DB::rollBack();

                return redirect()->back()->with('error', 'الشيك غير مرتبط بشركة');
            }

            $bankTransactionId = null;
            if ($payment->bank_id) {
                $bank = Bank::findOrFail($payment->bank_id);
                $bank->amount = ((float) ($bank->amount ?? 0)) + (float) $payment->value;
                $bank->save();

                $bankTransaction = BankTrnsaction::create([
                    'bank_id' => $bank->id,
                    'company_id' => $company->id,
                    'user_id' => auth()->id(),
                    'name' => 'استحقاق شيك رقم: ' . ($payment->check_number ?? ''),
                    'type' => 1,
                    'amount' => (float) $payment->value,
                    'date' => now()->format('Y-m-d'),
                ]);
                $bankTransactionId = $bankTransaction->id;
            }

            $processed = [];
            if ($linkedToInvoice) {
                $payment->check_paid_at = now();
                if ($bankTransactionId !== null) {
                    $payment->bank_transaction_id = $bankTransactionId;
                }
                $payment->save();
            } else {
                $parsed = $this->parseCheckPaymentNotes($payment->notes);
                $noteSuffix = (string) ($payment->check_number ?? '');
                [$leftover, $processed] = $this->distributeClearedCheckToInvoices(
                    $company,
                    (float) $payment->value,
                    $parsed['invoice_ids'],
                    $bankTransactionId,
                    $payment->created_at ? $payment->created_at->format('Y-m-d') : null,
                    $payment->bank_id ? (int) $payment->bank_id : null,
                    $noteSuffix
                );
                if ($leftover > 0.0001) {
                    DB::rollBack();

                    return redirect()->back()->with(
                        'error',
                        'لا يمكن استحقاق الشيك بالكامل: المتبقي على الفواتير أقل من قيمة الشيك بمقدار ' . number_format($leftover, 2) . ' ج.م'
                    );
                }
                $payment->delete();
            }

            DB::commit();

            $msg = 'تم استحقاق الشيك وتسجيل المبلغ على الحساب البنكي';
            if ($linkedToInvoice) {
                return redirect()->back()->with('success', $msg);
            }

            return redirect()->back()->with('success', $msg . (count($processed) ? ' (' . count($processed) . ' فاتورة)' : ''));
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'حدث خطأ أثناء استحقاق الشيك: ' . $e->getMessage());
        }
    }

    /**
     * سدادات الشركة: مرتبطة بفاتورة أو سجل شيك/سداد على مستوى الشركة (invoice_id فارغ).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private function applyCompanyInvoicePaymentsScope($query, int $companyId): void
    {
        $query->where(function ($outer) use ($companyId) {
            $outer->whereHas('invoice.booking', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->orWhere(function ($q) use ($companyId) {
                $q->whereNull('invoice_id')->where('company_id', $companyId);
            });
        });
    }

    /**
     * @return array{invoice_ids: array<int, int>, user_notes: ?string}
     */
    private function parseCheckPaymentNotes(?string $notes): array
    {
        $out = ['invoice_ids' => [], 'user_notes' => null];
        if ($notes === null || $notes === '') {
            return $out;
        }
        $decoded = json_decode($notes, true);
        if (is_array($decoded) && isset($decoded['invoice_ids']) && is_array($decoded['invoice_ids'])) {
            $out['invoice_ids'] = array_values(array_filter(array_map('intval', $decoded['invoice_ids'])));
            $out['user_notes'] = isset($decoded['user_notes']) ? (string) $decoded['user_notes'] : null;

            return $out;
        }
        $out['user_notes'] = $notes;

        return $out;
    }

    private function prepareCompanyCheckPaymentData(Request $request, Company $company, float $paymentAmount): array
    {
        $meta = [];
        if ($request->filled('invoice_ids')) {
            $meta['invoice_ids'] = array_values(array_filter(array_map('intval', explode(',', (string) $request->invoice_ids))));
        }
        if ($request->filled('notes')) {
            $meta['user_notes'] = (string) $request->notes;
        }

        $paymentData = [
            'invoice_id' => null,
            'company_id' => $company->id,
            'value' => $paymentAmount,
            'currency' => InvoiceCurrency::normalize($request->currency ?? InvoiceCurrency::EGP),
            'user_id' => auth()->id(),
            'payment_type' => 'check',
            'bank_id' => $request->bank_id,
            'check_bank_name' => $request->check_bank_name,
            'check_number' => $request->check_number,
            'check_due_date' => $request->check_due_date,
            'notes' => $meta !== [] ? json_encode($meta, JSON_UNESCAPED_UNICODE) : '',
        ];

        if ($request->hasFile('image')) {
            $imageName = time() . '_payment.' . $request->image->extension();
            $imagePath = $request->image->storeAs('invoice_payments', $imageName, 'public');
            $paymentData['image'] = 'storage/' . $imagePath;
        } else {
            $paymentData['image'] = '';
        }

        return $paymentData;
    }

    /**
     * توزيع مبلغ مستحق على فواتير الشركة (FIFO) بعد استحقاق الشيك في البنك.
     *
     * @return array{0: float, 1: list<string>}
     */
    private function distributeClearedCheckToInvoices(
        Company $company,
        float $amount,
        array $preferredInvoiceIds,
        ?int $bankTransactionId,
        ?string $paymentDate,
        ?int $bankId,
        string $noteSuffix
    ): array {
        $remaining = $amount;
        $processed = [];

        $invoices = collect();
        if ($preferredInvoiceIds !== []) {
            $invoices = Invoice::whereIn('id', $preferredInvoiceIds)
                ->whereHas('booking', function ($q) use ($company) {
                    $q->where('company_id', $company->id);
                })
                ->with('booking')
                ->get()
                ->sortBy(function ($inv) use ($preferredInvoiceIds) {
                    $pos = array_search((int) $inv->id, $preferredInvoiceIds, true);

                    return $pos === false ? 999999 : $pos;
                })
                ->values();
        }

        if ($invoices->isEmpty()) {
            $invoices = $company->bookings()
                ->whereHas('invoice')
                ->with('invoice')
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn ($b) => $b->invoice)
                ->filter();
        }

        foreach ($invoices as $invoice) {
            if ($remaining <= 0) {
                break;
            }
            if (!$invoice) {
                continue;
            }
            $invoiceTotal = $this->calculateInvoiceTotal($invoice);
            $paidAmount = $this->calculatePaidAmount($invoice);
            $remainingAmount = $invoiceTotal - $paidAmount;
            if ($remainingAmount <= 0) {
                continue;
            }
            $paymentValue = min($remainingAmount, $remaining);
            $row = InvoicePayment::create([
                'invoice_id' => $invoice->id,
                'company_id' => $company->id,
                'value' => $paymentValue,
                'payment_type' => 'bank_transfer',
                'user_id' => auth()->id(),
                'bank_id' => $bankId,
                'bank_transaction_id' => $bankTransactionId,
                'image' => '',
                'notes' => 'استحقاق شيك' . ($noteSuffix !== '' ? ' — ' . $noteSuffix : ''),
            ]);
            if ($paymentDate) {
                $row->created_at = Carbon::parse($paymentDate);
                $row->save();
            }
            $processed[] = $invoice->invoice_number ?? (string) $invoice->id;
            $remaining -= $paymentValue;
        }

        return [$remaining, $processed];
    }

    /**
     * حساب الرصيد المرحّل من الفواتير السابقة (EGP فقط — للتوافق).
     */
    private function calculateCarriedForwardBalance($company, $fromDate)
    {
        return $this->calculateCarriedForwardBalances($company, $fromDate)[InvoiceCurrency::EGP];
    }

    /**
     * @return array{EGP: float, USD: float}
     */
    private function calculateCarriedForwardBalances($company, $fromDate): array
    {
        $invoiceBuckets = CompanyCurrencyLedger::zero();

        $invoices = $company->bookings()
            ->whereHas('invoice', function ($query) use ($fromDate) {
                $query->whereDate('created_at', '<', $fromDate);
            })
            ->with(['invoice.invoicePayments'])
            ->get();

        foreach ($invoices as $booking) {
            $invoice = $booking->invoice;
            if ($invoice) {
                $invoiceBuckets = CompanyCurrencyLedger::add(
                    $invoiceBuckets,
                    CompanyCurrencyLedger::invoiceTotals($invoice, fn ($inv) => $this->calculateInvoiceTotal($inv))
                );
            }
        }

        $payments = InvoicePayment::query()
            ->tap(fn ($q) => $this->applyCompanyInvoicePaymentsScope($q, (int) $company->id))
            ->whereDate('created_at', '<', $fromDate)
            ->where(function ($query) {
                $query->where('payment_type', '!=', 'check')
                    ->orWhere(function ($q) {
                        $q->where('payment_type', 'check')
                            ->whereNotNull('check_paid_at');
                    });
            })
            ->get();

        $paymentBuckets = CompanyCurrencyLedger::paymentBuckets($payments);

        $opening = [
            InvoiceCurrency::EGP => (float) ($company->opening_balance ?? 0),
            InvoiceCurrency::USD => (float) ($company->opening_balance_usd ?? 0),
        ];

        return CompanyCurrencyLedger::sub(
            CompanyCurrencyLedger::add($opening, $invoiceBuckets),
            $paymentBuckets
        );
    }

    /**
     * @param  \Illuminate\Support\Collection  $invoices
     * @return array{EGP: float, USD: float}
     */
    private function sumInvoiceBuckets($invoices): array
    {
        $buckets = CompanyCurrencyLedger::zero();
        foreach ($invoices as $row) {
            $buckets = CompanyCurrencyLedger::add($buckets, [
                InvoiceCurrency::EGP => (float) ($row['total_egp'] ?? $row['total'] ?? 0),
                InvoiceCurrency::USD => (float) ($row['total_usd'] ?? 0),
            ]);
        }

        return $buckets;
    }

    /**
     * @param  array{EGP?: float, USD?: float}  $buckets
     */
    private function formatDualCurrencyLabel(array $buckets): string
    {
        $parts = [];
        if (round((float) ($buckets[InvoiceCurrency::EGP] ?? 0), 2) != 0.0) {
            $parts[] = 'ج.م';
        }
        if (round((float) ($buckets[InvoiceCurrency::USD] ?? 0), 2) != 0.0) {
            $parts[] = '$';
        }

        return $parts ? implode(' + ', $parts) : 'ج.م';
    }

    /**
     * جلب الفواتير في الفترة المحددة
     */
    private function getInvoicesInPeriod($company, $fromDate, $toDate)
    {
        return $company->bookings()
            ->whereHas('invoice', function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59']);
            })
            ->with(['invoice.invoicePayments'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($booking) {
                $invoice = $booking->invoice;
                if (! $invoice) {
                    return null;
                }
                $totals = CompanyCurrencyLedger::invoiceTotals($invoice, fn ($inv) => $this->calculateInvoiceTotal($inv));
                $paid = CompanyCurrencyLedger::invoicePaid($invoice);
                $remaining = CompanyCurrencyLedger::sub($totals, $paid);

                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'booking_number' => $booking->booking_number,
                    'date' => $invoice->created_at,
                    'currency' => InvoiceCurrency::normalize($invoice->currency ?? InvoiceCurrency::EGP),
                    'total' => $totals[InvoiceCurrency::EGP] ?: $totals[InvoiceCurrency::USD],
                    'total_egp' => $totals[InvoiceCurrency::EGP],
                    'total_usd' => $totals[InvoiceCurrency::USD],
                    'paid' => $paid[InvoiceCurrency::EGP] + $paid[InvoiceCurrency::USD],
                    'paid_egp' => $paid[InvoiceCurrency::EGP],
                    'paid_usd' => $paid[InvoiceCurrency::USD],
                    'remaining' => $remaining[InvoiceCurrency::EGP] + $remaining[InvoiceCurrency::USD],
                    'remaining_egp' => $remaining[InvoiceCurrency::EGP],
                    'remaining_usd' => $remaining[InvoiceCurrency::USD],
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * حساب إجمالي الفواتير
     */
    private function calculateTotalInvoices($invoices)
    {
        return $invoices->sum('total');
    }

    /**
     * حساب إجمالي السداد في الفترة (استبعاد الشيكات غير المستحقة)
     */
    private function calculateTotalPayments($company, $fromDate, $toDate)
    {
        return InvoicePayment::query()
            ->tap(fn ($q) => $this->applyCompanyInvoicePaymentsScope($q, (int) $company->id))
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->where(function ($query) {
                $query->where('payment_type', '!=', 'check')
                    ->orWhere(function ($q) {
                        $q->where('payment_type', 'check')
                            ->whereNotNull('check_paid_at');
                    });
            })
            ->sum('value');
    }

    /**
     * جلب تفاصيل السداد في الفترة
     */
    private function getPaymentsInPeriod($company, $fromDate, $toDate)
    {
        return InvoicePayment::query()
            ->tap(fn ($q) => $this->applyCompanyInvoicePaymentsScope($q, (int) $company->id))
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->with(['invoice.booking', 'bank'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * حساب المبلغ الإجمالي للفاتورة
     */
    private function calculateInvoiceTotal($invoice)
    {
        $invoiceTotalBeforeTax = $invoice->invoice_total_before_tax ?? 0;
        $taxedServicesTotal = $invoice->taxed_services_total_before_vat ?? 0;
        $untaxedServicesTotal = $invoice->untaxed_services_total_before_vat ?? 0;
        $vatValue = $invoice->value_added_tax_amount ?? 0;
        $saleValue = $invoice->sales_tax_amount ?? 0;
        $discountValue = $invoice->discount_amount ?? 0;

        return ceil($invoiceTotalBeforeTax + $taxedServicesTotal + $untaxedServicesTotal + $vatValue - $saleValue - $discountValue);
    }

    /**
     * حساب المدفوعات الفعلية (استبعاد الشيكات غير المستحقة) — إجمالي كل العملات.
     */
    private function calculatePaidAmount($invoice)
    {
        $paid = CompanyCurrencyLedger::invoicePaid($invoice);

        return $paid[InvoiceCurrency::EGP] + $paid[InvoiceCurrency::USD];
    }

    /**
     * حساب الرصيد المستحق الحالي (EGP فقط — للتوافق).
     */
    private function calculateCurrentBalance($company)
    {
        return $this->calculateCurrentBalances($company)[InvoiceCurrency::EGP];
    }

    /**
     * @return array{EGP: float, USD: float}
     */
    private function calculateCurrentBalances($company): array
    {
        $invoiceBuckets = CompanyCurrencyLedger::zero();
        $paidBuckets = CompanyCurrencyLedger::zero();

        $invoices = $company->bookings()
            ->whereHas('invoice')
            ->with(['invoice.invoicePayments'])
            ->get();

        foreach ($invoices as $booking) {
            $invoice = $booking->invoice;
            if (! $invoice) {
                continue;
            }
            $invoiceBuckets = CompanyCurrencyLedger::add(
                $invoiceBuckets,
                CompanyCurrencyLedger::invoiceTotals($invoice, fn ($inv) => $this->calculateInvoiceTotal($inv))
            );
            $paidBuckets = CompanyCurrencyLedger::add($paidBuckets, CompanyCurrencyLedger::invoicePaid($invoice));
        }

        $opening = [
            InvoiceCurrency::EGP => (float) ($company->opening_balance ?? 0),
            InvoiceCurrency::USD => (float) ($company->opening_balance_usd ?? 0),
        ];

        return CompanyCurrencyLedger::sub(
            CompanyCurrencyLedger::add($opening, $invoiceBuckets),
            $paidBuckets
        );
    }

    /**
     * عرض كشف حساب لسيارة معينة (نفس منطق صفحة السداد: تكلفة - عهدة + مصروفات إضافية - دفعات)
     */
    public function carStatement(Request $request, $carId)
    {
        $car = Car::findOrFail($carId);

        $fromDate = $request->from ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $toDate = $request->to ?? Carbon::now()->format('Y-m-d');

        // حساب الرصيد المرحّل (مجموع المتبقي لكل نقلة قبل من)
        $carriedForwardBalance = $this->calculateCarCarriedForwardBalance($car, $fromDate);

        // جلب النقلات في الفترة للإجماليات
        $policiesInPeriod = DeliveryPolicy::where('car_id', $car->id)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->with(['money_transfer', 'settled_money_transfer', 'extraExpenses', 'payingCars'])
            ->get();

        $totalValue = $policiesInPeriod->sum(fn ($p) => (float) ($p->cost ?? 0));
        $totalCustody = $policiesInPeriod->sum(fn ($p) => (float) (($p->money_transfer?->value ?? 0) - ($p->settled_money_transfer?->value ?? 0)));
        $totalPayments = Payingcar::where('car_id', $car->id)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->sum('value');

        // الرصيد النهائي = مجموع المتبقي لجميع النقلات حتى نهاية الفترة (نفس معادلة صفحة السداد)
        $allPoliciesToDate = DeliveryPolicy::where('car_id', $car->id)
            ->where('created_at', '<=', $toDate . ' 23:59:59')
            ->with(['money_transfer', 'settled_money_transfer', 'extraExpenses', 'payingCars'])
            ->get();
        $finalBalance = $allPoliciesToDate->sum(fn ($p) => $this->getDeliveryPolicyRemaining($p));

        // بناء قائمة الحركات للعرض
        $transactions = $this->buildCarTransactionsList($car, $fromDate, $toDate, $carriedForwardBalance);

        return view('admin.accounts.car-statement', compact(
            'car',
            'fromDate',
            'toDate',
            'carriedForwardBalance',
            'transactions',
            'totalValue',
            'totalCustody',
            'totalPayments',
            'finalBalance'
        ));
    }

    /**
     * تصدير كشف حساب السيارة إلى Excel
     */
    public function exportCarExcel(Request $request, $carId)
    {
        $car = Car::findOrFail($carId);

        $fromDate = $request->from ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $toDate = $request->to ?? Carbon::now()->format('Y-m-d');

        $carriedForwardBalance = $this->calculateCarCarriedForwardBalance($car, $fromDate);

        $policiesInPeriod = DeliveryPolicy::where('car_id', $car->id)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->with(['money_transfer', 'settled_money_transfer', 'extraExpenses', 'payingCars'])
            ->get();
        $totalValue = $policiesInPeriod->sum(fn ($p) => (float) ($p->cost ?? 0));
        $totalCustody = $policiesInPeriod->sum(fn ($p) => (float) (($p->money_transfer?->value ?? 0) - ($p->settled_money_transfer?->value ?? 0)));
        $totalPayments = Payingcar::where('car_id', $car->id)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->sum('value');
        $allPoliciesToDate = DeliveryPolicy::where('car_id', $car->id)
            ->where('created_at', '<=', $toDate . ' 23:59:59')
            ->with(['money_transfer', 'settled_money_transfer', 'extraExpenses', 'payingCars'])
            ->get();
        $finalBalance = $allPoliciesToDate->sum(fn ($p) => $this->getDeliveryPolicyRemaining($p));

        $transactions = $this->buildCarTransactionsList($car, $fromDate, $toDate, $carriedForwardBalance);

        $fileName = 'كشف_حساب_سيارة_' . $car->car_number . '_' . $fromDate . '_' . $toDate . '.xlsx';

        return Excel::download(
            new CarStatementExport($car, $fromDate, $toDate, $carriedForwardBalance, $transactions, $totalValue, $totalCustody, $totalPayments, $finalBalance),
            $fileName
        );
    }

    /**
     * تصدير كشف حساب السيارة إلى PDF
     */
    public function exportCarPDF(Request $request, $carId)
    {
        $car = Car::findOrFail($carId);

        $fromDate = $request->from ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $toDate = $request->to ?? Carbon::now()->format('Y-m-d');

        $carriedForwardBalance = $this->calculateCarCarriedForwardBalance($car, $fromDate);

        $policiesInPeriod = DeliveryPolicy::where('car_id', $car->id)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->with(['money_transfer', 'settled_money_transfer', 'extraExpenses', 'payingCars'])
            ->get();
        $totalValue = $policiesInPeriod->sum(fn ($p) => (float) ($p->cost ?? 0));
        $totalCustody = $policiesInPeriod->sum(fn ($p) => (float) (($p->money_transfer?->value ?? 0) - ($p->settled_money_transfer?->value ?? 0)));
        $totalPayments = Payingcar::where('car_id', $car->id)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->sum('value');
        $allPoliciesToDate = DeliveryPolicy::where('car_id', $car->id)
            ->where('created_at', '<=', $toDate . ' 23:59:59')
            ->with(['money_transfer', 'settled_money_transfer', 'extraExpenses', 'payingCars'])
            ->get();
        $finalBalance = $allPoliciesToDate->sum(fn ($p) => $this->getDeliveryPolicyRemaining($p));

        $transactions = $this->buildCarTransactionsList($car, $fromDate, $toDate, $carriedForwardBalance);

        $html = view('admin.accounts.car-statement-pdf', compact(
            'car',
            'fromDate',
            'toDate',
            'carriedForwardBalance',
            'transactions',
            'totalValue',
            'totalCustody',
            'totalPayments',
            'finalBalance'
        ))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
            'margin_left' => 6,
            'margin_right' => 6,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'margin_header' => 4,
            'margin_footer' => 4,
        ]);

        $mpdf->WriteHTML($html);

        $fileName = 'كشف_حساب_سيارة_' . $car->car_number . '_' . $fromDate . '_' . $toDate . '.pdf';

        return $mpdf->Output($fileName, 'D');
    }

    /**
     * حساب المتبقي لنقلة: تكلفة - صافي العهدة + مصروفات إضافية - دفعات
     * صافي العهدة = العهدة المُعطاة - العهدة المُسددة (إن وُجدت تسوية)
     */
    private function getDeliveryPolicyRemaining($policy)
    {
        $cost = (float) ($policy->cost ?? 0);
        $custodyGiven = (float) ($policy->money_transfer?->value ?? 0);
        $custodySettled = (float) ($policy->settled_money_transfer?->value ?? 0);
        $netCustody = $custodyGiven - $custodySettled;
        $extraExpenses = (float) ($policy->extraExpenses->sum('value') ?? 0);
        $payments = (float) ($policy->payingCars->sum('value') ?? 0);
        return $cost
            ? $cost - $netCustody + $extraExpenses - $payments
            : $extraExpenses + $payments - $netCustody;
    }

    /**
     * حساب الرصيد المرحّل للسيارة (نفس منطق السداد: مجموع المتبقي لكل نقلة قبل تاريخ البداية)
     */
    private function calculateCarCarriedForwardBalance($car, $fromDate)
    {
        $policies = DeliveryPolicy::where('car_id', $car->id)
            ->whereDate('created_at', '<', $fromDate)
            ->with(['money_transfer', 'settled_money_transfer', 'extraExpenses', 'payingCars'])
            ->get();

        $balance = 0;
        foreach ($policies as $policy) {
            $balance += $this->getDeliveryPolicyRemaining($policy);
        }
        return $balance;
    }

    /**
     * بناء قائمة الحركات لكشف حساب السيارة (نفس معادلة السداد: تكلفة - عهدة + مصروفات إضافية - دفعات)
     */
    private function buildCarTransactionsList($car, $fromDate, $toDate, $carriedForwardBalance)
    {
        $transactions = collect();

        // إضافة الرصيد المرحّل
        if ($carriedForwardBalance != 0) {
            $startDate = Carbon::parse($fromDate)->subDay();
            $transactions->push([
                'date' => $startDate,
                'type' => 'carried_forward',
                'type_label' => 'رصيد مرحّل',
                'previous_balance' => abs($carriedForwardBalance),
                'service' => '',
                'description' => 'رصيد مرحّل من الفترة السابقة',
                'container_no' => '',
                'departure' => '',
                'destination' => '',
                'aging' => '',
                'value' => 0,
                'custody' => 0,
                'total1' => 0,
                'total2' => 0,
                'debit_credit' => $carriedForwardBalance >= 0 ? 'مدين' : 'دائن',
                'running_total' => $carriedForwardBalance,
                'running_balance' => $carriedForwardBalance,
                'sort_tie' => 0,
            ]);
        }

        $deliveryPolicies = DeliveryPolicy::where('car_id', $car->id)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->with(['money_transfer', 'settled_money_transfer', 'booking_containers.departure', 'booking_containers.loading', 'booking_containers.aging', 'extraExpenses', 'payingCars'])
            ->orderBy('created_at', 'asc')
            ->get();

        $sortTie = 0;

        foreach ($deliveryPolicies as $policy) {
            $cost = (float) ($policy->cost ?? 0);
            $custodyGiven = (float) ($policy->money_transfer?->value ?? 0);
            $custodySettled = (float) ($policy->settled_money_transfer?->value ?? 0);
            $custodyAmount = $custodyGiven - $custodySettled;
            $containers = $policy->booking_containers;
            $firstContainer = $containers->first();
            $container = $firstContainer ? BookingContainer::with(['departure', 'loading', 'aging'])->find($firstContainer->id) : null;

            $sortTie++;
            $transactions->push([
                'date' => $policy->created_at,
                'type' => 'delivery_policy',
                'type_label' => 'نقلة',
                'previous_balance' => 0,
                'service' => 'نقلة',
                'description' => 'نقلة',
                'container_no' => $container ? ($container->container_no ?? $container->sail_of_number ?? '') : '',
                'departure' => $container && $container->departure ? $container->departure->title : '',
                'destination' => $container && $container->loading ? $container->loading->title : '',
                'aging' => $container && $container->aging ? $container->aging->title : '',
                'value' => $cost,
                'custody' => $custodyAmount,
                'total1' => $cost,
                'total2' => $custodyAmount,
                'debit_credit' => ($cost - $custodyAmount) >= 0 ? 'مدين' : 'دائن',
                'running_total' => 0,
                'running_balance' => 0,
                'sort_tie' => $sortTie,
            ]);

            foreach ($policy->extraExpenses as $extraExpense) {
                $container = null;
                if ($extraExpense->booking_container_id) {
                    $container = BookingContainer::with(['departure', 'loading', 'aging'])->find($extraExpense->booking_container_id);
                } elseif ($firstContainer) {
                    $container = BookingContainer::with(['departure', 'loading', 'aging'])->find($firstContainer->id);
                }
                $sortTie++;
                $transactions->push([
                    'date' => $extraExpense->created_at,
                    'type' => 'extra_expense',
                    'type_label' => 'مصروف إضافي',
                    'previous_balance' => 0,
                    'service' => 'مصروف إضافي',
                    'description' => $extraExpense->name ?? 'مصروف إضافي',
                    'container_no' => $container ? ($container->container_no ?? $container->sail_of_number ?? '') : '',
                    'departure' => $container && $container->departure ? $container->departure->title : '',
                    'destination' => $container && $container->loading ? $container->loading->title : '',
                    'aging' => $container && $container->aging ? $container->aging->title : '',
                    'value' => 0,
                    'custody' => 0,
                    'total1' => 0,
                    'total2' => $extraExpense->value,
                    'debit_credit' => 'مدين',
                    'running_total' => 0,
                    'running_balance' => 0,
                    'sort_tie' => $sortTie,
                ]);
            }

            foreach ($policy->payingCars as $payment) {
                if (!empty($payment->payment_group_uuid)) {
                    continue;
                }
                $sortTie++;
                $transactions->push([
                    'date' => $payment->created_at,
                    'type' => 'payment',
                    'type_label' => 'دفعة',
                    'previous_balance' => 0,
                    'service' => 'دفعة',
                    'description' => $payment->notes ?? '',
                    'container_no' => '',
                    'departure' => '',
                    'destination' => '',
                    'aging' => '',
                    'value' => 0,
                    'custody' => 0,
                    'total1' => 0,
                    'total2' => (float) $payment->value,
                    'debit_credit' => 'دائن',
                    'running_total' => 0,
                    'running_balance' => 0,
                    'sort_tie' => $sortTie,
                ]);
            }
        }

        $batched = Payingcar::where('car_id', $car->id)
            ->whereNotNull('payment_group_uuid')
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->with(['delivery_policy.booking_containers'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('payment_group_uuid')
            ->sortBy(fn ($group) => $group->first()->created_at->timestamp);

        foreach ($batched as $uuid => $payments) {
            $payments = $payments->values();
            $totalBatch = $payments->sum(fn ($p) => (float) $p->value);
            $paymentDetails = $payments->map(function ($p) {
                $policy = $p->delivery_policy;
                $containerNos = $policy && $policy->booking_containers
                    ? $policy->booking_containers->pluck('container_no')->filter()->implode(', ')
                    : '';

                return [
                    'id' => $p->id,
                    'delivery_policy_id' => $policy?->id,
                    'container_numbers' => $containerNos !== '' ? $containerNos : '-',
                    'value' => (float) $p->value,
                    'notes' => '',
                ];
            })->values()->toArray();

            $sortTie++;
            $batchDate = $payments->first()->created_at;

            $transactions->push([
                'date' => $batchDate,
                'type' => 'payment_group',
                'type_label' => 'سداد',
                'previous_balance' => 0,
                'service' => 'سداد',
                'description' => $payments->count() > 1
                    ? 'سداد متعدد (' . $payments->count() . ' نقلة)'
                    : 'سداد',
                'container_no' => '',
                'departure' => '',
                'destination' => '',
                'aging' => '',
                'value' => 0,
                'custody' => 0,
                'total1' => 0,
                'total2' => $totalBatch,
                'debit_credit' => 'دائن',
                'running_total' => 0,
                'running_balance' => 0,
                'sort_tie' => $sortTie,
                'payment_details' => $paymentDetails,
                'payment_count' => $payments->count(),
                'payment_group_uuid' => (string) $uuid,
            ]);
        }

        $sorted = $transactions->sortBy(function ($row) {
            $d = $row['date'] instanceof Carbon ? $row['date'] : Carbon::parse($row['date']);

            return sprintf('%s-%06d', $d->format('Y-m-d H:i:s'), (int) ($row['sort_tie'] ?? 0));
        })->values();

        return $this->recalculateCarStatementRunningBalances($sorted, $carriedForwardBalance);
    }

    /**
     * إعادة حساب الرصيد التراكمي بعد ترتيب الحركات (نقلة / مصروف / دفعة / سداد مجمع)
     */
    private function recalculateCarStatementRunningBalances($transactions, float $carriedForwardBalance)
    {
        $running = (float) $carriedForwardBalance;

        return $transactions->map(function ($row) use (&$running, $carriedForwardBalance) {
            switch ($row['type']) {
                case 'carried_forward':
                    $running = (float) $carriedForwardBalance;
                    break;
                case 'delivery_policy':
                    $running += (float) $row['value'] - (float) $row['custody'];
                    break;
                case 'extra_expense':
                    $running += (float) $row['total2'];
                    break;
                case 'payment':
                case 'payment_group':
                    $running -= (float) $row['total2'];
                    break;
            }
            $row['running_balance'] = $running;
            $row['running_total'] = $running;

            return $row;
        })->values();
    }

    /**
     * عرض صفحة السداد للسيارة
     */
    public function showCarPaymentForm($carId)
    {
        $car = Car::findOrFail($carId);

        // جلب جميع النقلات (delivery policies) للسيارة التي لها رصيد مستحق
        $unpaidShipments = collect();
        $deliveryPolicies = DeliveryPolicy::where('car_id', $carId)
            ->with(['money_transfer', 'settled_money_transfer', 'extraExpenses', 'payingCars', 'booking_containers.departure', 'booking_containers.loading', 'booking_containers.aging'])
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($deliveryPolicies as $policy) {
            $cost = $policy->cost ?? 0;
            $custodyGiven = (float) ($policy->money_transfer?->value ?? 0);
            $custodySettled = (float) ($policy->settled_money_transfer?->value ?? 0);
            $financialCustody = $custodyGiven - $custodySettled;
            $extraExpenses = $policy->extraExpenses->sum('value') ?? 0;
            $payments = $policy->payingCars->sum('value') ?? 0;

            // حساب المتبقي (صافي العهدة = عهدة - عهدة مسددة)
            $remain = $cost
                ? $cost - $financialCustody + $extraExpenses - $payments
                : $extraExpenses + $payments - $financialCustody;

            // إضافة النقلة فقط إذا كان لديها رصيد مستحق
            if ($remain > 0) {
                $containerNumbers = $policy->booking_containers
                    ? implode(', ', $policy->booking_containers->pluck('container_no')->filter()->toArray())
                    : '';

                $unpaidShipments->push([
                    'id' => $policy->id,
                    'container_numbers' => $containerNumbers,
                    'date' => $policy->date ?? $policy->created_at,
                    'cost' => $cost,
                    'financial_custody' => $financialCustody,
                    'extra_expenses' => $extraExpenses,
                    'paid' => $payments,
                    'remaining' => $remain,
                    'departure' => $policy->booking_containers->first()->departure->title ?? '',
                    'loading' => $policy->booking_containers->first()->loading->title ?? '',
                    'aging' => $policy->booking_containers->first()->aging->title ?? '',
                ]);
            }
        }

        // الرصيد المستحق (نقلات غير مسددة فقط - نفس المنطق أعلاه)
        $currentBalance = $unpaidShipments->sum('remaining');

        // الرصيد النهائي (مجموع المتبقي لجميع النقلات - يطابق كشف الحساب)
        $allPolicies = DeliveryPolicy::where('car_id', $car->id)
            ->with(['money_transfer', 'settled_money_transfer', 'extraExpenses', 'payingCars'])
            ->get();
        $finalBalance = $allPolicies->sum(fn ($p) => $this->getDeliveryPolicyRemaining($p));

        return view('admin.accounts.car-payment', compact('car', 'unpaidShipments', 'currentBalance', 'finalBalance'));
    }

    /**
     * معالجة السداد للسيارة
     */
    public function processCarPayment(Request $request, $carId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'shipment_ids' => 'required|string', // comma-separated IDs (نطاق التوزيع)
        ]);

        $car = Car::findOrFail($carId);
        $vault = Vault::first();

        if (!$vault) {
            return redirect()->back()->with('error', 'لا توجد خزنة في النظام');
        }

        $shipmentIds = collect(explode(',', (string) $request->shipment_ids))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($shipmentIds->isEmpty()) {
            return redirect()->back()->with('error', 'يجب تحديد نقلات على الأقل');
        }

        $policies = DeliveryPolicy::query()
            ->whereIn('id', $shipmentIds)
            ->where('car_id', $carId)
            ->orderBy('created_at', 'asc')
            ->with(['money_transfer', 'settled_money_transfer', 'extraExpenses', 'payingCars'])
            ->get();

        if ($policies->count() !== $shipmentIds->count()) {
            return redirect()->back()->with('error', 'يوجد نقلات غير صالحة أو لا تخص هذه السيارة');
        }

        $maxAllocatable = $policies->sum(fn ($p) => max(0.0, (float) $this->getDeliveryPolicyRemaining($p)));
        $paymentAmount = round((float) $request->amount, 2);

        if ($paymentAmount > $maxAllocatable + 0.01) {
            return redirect()->back()->with(
                'error',
                'المبلغ أكبر من إجمالي المتبقي للنقلات المحددة. المتاح: ' . number_format($maxAllocatable, 2) . ' جنيه'
            );
        }

        DB::beginTransaction();

        try {
            $totalPaymentAmount = 0;
            $processedShipments = [];
            $processedShipmentAmounts = [];
            $remainingPayment = $paymentAmount;
            $paymentGroupUuid = (string) Str::uuid();

            foreach ($policies as $policy) {
                if ($remainingPayment <= 0.00001) {
                    break;
                }

                $owing = (float) $this->getDeliveryPolicyRemaining($policy);
                if ($owing <= 0.00001) {
                    continue;
                }

                $payThis = min($owing, $remainingPayment);
                if ($payThis <= 0.00001) {
                    continue;
                }

                $paymentData = [
                    'delivery_policy_id' => $policy->id,
                    'car_id' => $car->id,
                    'value' => $payThis,
                    'user_id' => auth()->id(),
                    'payment_group_uuid' => $paymentGroupUuid,
                ];

                if ($request->hasFile('image')) {
                    $imageName = time() . '_car_payment_' . $policy->id . '.' . $request->image->extension();
                    $this->uploadImage($request->image, $imageName, 'banks');
                    $paymentData['image'] = 'Admin/images/banks/' . $imageName;
                }

                $payingCar = Payingcar::create($paymentData);

                if ($request->payment_date) {
                    $payingCar->created_at = Carbon::parse($request->payment_date)->endOfDay();
                    $payingCar->save();
                }

                MoneyTransfer::create([
                    'value' => $payThis,
                    'transfered_type' => 'App\Models\Payingcar',
                    'transfered_id' => $payingCar->id,
                    'transferer_type' => 'App\Models\User',
                    'transferer_id' => auth()->id(),
                    'type' => 7, // carPayment
                ]);

                $vault->amount = ($vault->amount ?? 0) - $payThis;
                $vault->save();

                VaultTransaction::create([
                    'name' => 'سداد نقلة - ' . $car->car_number,
                    'amount' => $payThis,
                    'type' => 0, // منصرف
                ]);

                $totalPaymentAmount += $payThis;
                $processedShipments[] = $policy->id;
                $processedShipmentAmounts[] = $payThis;
                $remainingPayment -= $payThis;
            }

            if ($totalPaymentAmount <= 0) {
                DB::rollBack();
                return redirect()->back()->with('error', 'لا توجد مبالغ مستحقة في النقلات المحددة');
            }

            if ($remainingPayment > 0.01) {
                DB::rollBack();
                return redirect()->back()->with('error', 'تعذر توزيع المبلغ بالكامل على النقلات المحددة');
            }

            DB::commit();

            $message = 'تم تسجيل السداد بنجاح. المبلغ الإجمالي: ' . number_format($totalPaymentAmount, 2) . ' جنيه (' . count($processedShipments) . ' نقلة)';

            return redirect()->route('accounts.car.payment', $carId)
                ->with('success', $message)
                ->with('processed_shipments', $processedShipments)
                ->with('processed_shipment_amounts', $processedShipmentAmounts)
                ->with('payment_group_uuid', $paymentGroupUuid);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ أثناء تسجيل السداد: ' . $e->getMessage());
        }
    }

    /**
     * صفحة HTML لطباعة بيان سداد نقلات (مجموعة سداد واحدة من كشف الحساب)
     */
    public function carPaymentGroupReceiptPrint(Request $request, $carId)
    {
        $car = Car::findOrFail($carId);
        $groupUuid = trim((string) $request->query('group', ''));
        if ($groupUuid === '') {
            abort(404);
        }

        $payingCars = Payingcar::where('car_id', $car->id)
            ->where('payment_group_uuid', $groupUuid)
            ->orderBy('id')
            ->get();

        if ($payingCars->isEmpty()) {
            abort(404);
        }

        [$shipmentsData, $totalAmount] = $this->buildCarPaymentReceiptRowsFromPayingcars($car, $payingCars);
        $showPrintChrome = true;

        return view('admin.accounts.car-payment-pdf', compact('car', 'shipmentsData', 'totalAmount', 'showPrintChrome'));
    }

    /**
     * تحميل بيان سداد نقلات كـ PDF لمجموعة سداد (نفس محتوى المعاينة)
     */
    public function carPaymentGroupReceiptPdf(Request $request, $carId)
    {
        $car = Car::findOrFail($carId);
        $groupUuid = trim((string) $request->query('group', ''));
        if ($groupUuid === '') {
            abort(404);
        }

        $payingCars = Payingcar::where('car_id', $car->id)
            ->where('payment_group_uuid', $groupUuid)
            ->orderBy('id')
            ->get();

        if ($payingCars->isEmpty()) {
            abort(404);
        }

        [$shipmentsData, $totalAmount] = $this->buildCarPaymentReceiptRowsFromPayingcars($car, $payingCars);

        if (count($shipmentsData) === 0) {
            abort(404);
        }

        $html = view('admin.accounts.car-payment-pdf', compact('car', 'shipmentsData', 'totalAmount'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_header' => 6,
            'margin_footer' => 6,
        ]);

        $mpdf->WriteHTML($html);

        $fileName = 'بيان_سداد_نقلات_' . $car->car_number . '_' . date('Y-m-d') . '.pdf';

        return $mpdf->Output($fileName, 'D');
    }

    /**
     * بناء صفوف بيان السداد من سجلات Payingcar (مبالغ هذا السداد لكل نقلة)
     *
     * @return array{0: array, 1: float}
     */
    private function buildCarPaymentReceiptRowsFromPayingcars(Car $car, $payingCars): array
    {
        $policyIds = $payingCars->pluck('delivery_policy_id')->filter()->unique()->values();
        $policies = DeliveryPolicy::whereIn('id', $policyIds)
            ->where('car_id', $car->id)
            ->with(['money_transfer', 'settled_money_transfer', 'extraExpenses', 'payingCars', 'booking_containers.departure', 'booking_containers.loading', 'booking_containers.aging'])
            ->get()
            ->keyBy('id');

        $shipmentsData = [];
        $totalAmount = 0.0;

        foreach ($payingCars as $pc) {
            $policy = $policies->get($pc->delivery_policy_id);
            if (!$policy) {
                continue;
            }

            $receiptAmount = (float) $pc->value;
            if ($receiptAmount <= 0) {
                continue;
            }

            $cost = $policy->cost ?? 0;
            $custodyGiven = (float) ($policy->money_transfer?->value ?? 0);
            $custodySettled = (float) ($policy->settled_money_transfer?->value ?? 0);
            $financialCustody = $custodyGiven - $custodySettled;
            $extraExpenses = (float) ($policy->extraExpenses->sum('value') ?? 0);
            $payments = (float) ($policy->payingCars->sum('value') ?? 0);

            $firstContainer = $policy->booking_containers?->first();
            $containerNumbers = $policy->booking_containers
                ? implode(', ', $policy->booking_containers->pluck('container_no')->filter()->toArray())
                : '';

            $shipmentsData[] = [
                'id' => $policy->id,
                'container_numbers' => $containerNumbers,
                'date' => $policy->date ?? $policy->created_at,
                'cost' => (float) $cost,
                'financial_custody' => $financialCustody,
                'extra_expenses' => $extraExpenses,
                'paid' => $payments,
                'remaining' => $receiptAmount,
                'departure' => $firstContainer?->departure?->title ?? '',
                'loading' => $firstContainer?->loading?->title ?? '',
                'aging' => $firstContainer?->aging?->title ?? '',
            ];
            $totalAmount += $receiptAmount;
        }

        return [$shipmentsData, $totalAmount];
    }

    /**
     * تصدير بيان السداد للسيارة إلى PDF
     */
    public function exportCarPaymentPDF(Request $request, $carId)
    {
        $car = Car::findOrFail($carId);
        $shipmentIds = $request->get('shipment_ids', '');

        if (empty($shipmentIds)) {
            return redirect()->back()->with('error', 'يجب تحديد نقلات');
        }

        $shipmentIdsArray = array_values(array_filter(array_map('trim', explode(',', $shipmentIds))));
        $amountsParam = $request->get('amounts', '');
        $amountsArray = $amountsParam !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $amountsParam)), fn ($v) => $v !== ''))
            : [];

        $useReceiptAmounts = count($amountsArray) === count($shipmentIdsArray) && count($shipmentIdsArray) > 0;

        $deliveryPolicies = DeliveryPolicy::whereIn('id', $shipmentIdsArray)
            ->where('car_id', $carId)
            ->with(['money_transfer', 'settled_money_transfer', 'extraExpenses', 'payingCars', 'booking_containers.departure', 'booking_containers.loading', 'booking_containers.aging'])
            ->get()
            ->keyBy('id');

        $totalAmount = 0;
        $shipmentsData = [];

        foreach ($shipmentIdsArray as $index => $policyId) {
            $policy = $deliveryPolicies->get((int) $policyId);
            if (!$policy) {
                continue;
            }

            $cost = $policy->cost ?? 0;
            $custodyGiven = (float) ($policy->money_transfer?->value ?? 0);
            $custodySettled = (float) ($policy->settled_money_transfer?->value ?? 0);
            $financialCustody = $custodyGiven - $custodySettled;
            $extraExpenses = $policy->extraExpenses->sum('value') ?? 0;
            $payments = $policy->payingCars->sum('value') ?? 0;

            $remain = $cost
                ? $cost - $financialCustody + $extraExpenses - $payments
                : $extraExpenses + $payments - $financialCustody;

            $firstContainer = $policy->booking_containers?->first();

            if ($useReceiptAmounts) {
                $receiptAmount = (float) str_replace(',', '.', $amountsArray[$index] ?? '0');
                if ($receiptAmount <= 0) {
                    continue;
                }

                $containerNumbers = $policy->booking_containers
                    ? implode(', ', $policy->booking_containers->pluck('container_no')->filter()->toArray())
                    : '';

                $shipmentsData[] = [
                    'id' => $policy->id,
                    'container_numbers' => $containerNumbers,
                    'date' => $policy->date ?? $policy->created_at,
                    'cost' => $cost,
                    'financial_custody' => $financialCustody,
                    'extra_expenses' => $extraExpenses,
                    'paid' => $payments,
                    'remaining' => $receiptAmount,
                    'departure' => $firstContainer?->departure?->title ?? '',
                    'loading' => $firstContainer?->loading?->title ?? '',
                    'aging' => $firstContainer?->aging?->title ?? '',
                ];
                $totalAmount += $receiptAmount;
            } elseif ($remain > 0) {
                $containerNumbers = $policy->booking_containers
                    ? implode(', ', $policy->booking_containers->pluck('container_no')->filter()->toArray())
                    : '';

                $shipmentsData[] = [
                    'id' => $policy->id,
                    'container_numbers' => $containerNumbers,
                    'date' => $policy->date ?? $policy->created_at,
                    'cost' => $cost,
                    'financial_custody' => $financialCustody,
                    'extra_expenses' => $extraExpenses,
                    'paid' => $payments,
                    'remaining' => $remain,
                    'departure' => $firstContainer?->departure?->title ?? '',
                    'loading' => $firstContainer?->loading?->title ?? '',
                    'aging' => $firstContainer?->aging?->title ?? '',
                ];

                $totalAmount += $remain;
            }
        }

        $html = view('admin.accounts.car-payment-pdf', compact('car', 'shipmentsData', 'totalAmount'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_header' => 6,
            'margin_footer' => 6
        ]);

        $mpdf->WriteHTML($html);

        $fileName = 'بيان_سداد_نقلات_' . $car->car_number . '_' . date('Y-m-d') . '.pdf';

        return $mpdf->Output($fileName, 'D');
    }

    /**
     * عرض تقرير الموقف المالي - الشركات المدينة
     */
    public function financialPositionReport(Request $request)
    {
        $reportDate = $request->date ?? Carbon::now()->format('Y-m-d');

        // جلب جميع الشركات
        $companies = Company::with(['bookings.invoice.invoicePayments'])
            ->orderBy('name')
            ->get();

        $companiesWithDebts = collect();

        foreach ($companies as $company) {
            $invoiceBuckets = CompanyCurrencyLedger::zero();
            $paymentBuckets = CompanyCurrencyLedger::zero();

            $invoices = $company->bookings()
                ->whereHas('invoice', function ($query) use ($reportDate) {
                    $query->whereDate('created_at', '<=', $reportDate);
                })
                ->with(['invoice.invoicePayments'])
                ->get();

            foreach ($invoices as $booking) {
                $invoice = $booking->invoice;
                if (! $invoice) {
                    continue;
                }
                $invoiceBuckets = CompanyCurrencyLedger::add(
                    $invoiceBuckets,
                    CompanyCurrencyLedger::invoiceTotals($invoice, fn ($inv) => $this->calculateInvoiceTotal($inv))
                );
                foreach ($invoice->invoicePayments as $payment) {
                    if ($payment->created_at && $payment->created_at->format('Y-m-d') > $reportDate) {
                        continue;
                    }
                    if (! CompanyCurrencyLedger::paymentCountsTowardBalance($payment)) {
                        continue;
                    }
                    $currency = InvoiceCurrency::normalize($payment->currency ?? InvoiceCurrency::EGP);
                    $paymentBuckets[$currency] = round($paymentBuckets[$currency] + (float) $payment->value, 2);
                }
            }

            $opening = [
                InvoiceCurrency::EGP => (float) ($company->opening_balance ?? 0),
                InvoiceCurrency::USD => (float) ($company->opening_balance_usd ?? 0),
            ];
            $balances = CompanyCurrencyLedger::sub(
                CompanyCurrencyLedger::add($opening, $invoiceBuckets),
                $paymentBuckets
            );
            $balance = $balances[InvoiceCurrency::EGP];
            $balanceUsd = $balances[InvoiceCurrency::USD];

            if ($balance > 0 || $balanceUsd > 0) {
                $companiesWithDebts->push([
                    'id' => $company->id,
                    'name' => $company->name,
                    'email' => $company->email,
                    'phone' => $company->phone,
                    'total_invoices' => $invoiceBuckets[InvoiceCurrency::EGP],
                    'total_payments' => $paymentBuckets[InvoiceCurrency::EGP],
                    'balance' => $balance,
                    'balance_usd' => $balanceUsd,
                    'total_invoices_usd' => $invoiceBuckets[InvoiceCurrency::USD],
                    'total_payments_usd' => $paymentBuckets[InvoiceCurrency::USD],
                ]);
            }
        }

        // ترتيب حسب المبلغ المستحق (من الأكبر للأصغر)
        $companiesWithDebts = $companiesWithDebts->sortByDesc('balance')->values();

        // حساب الإجمالي
        $totalDebts = $companiesWithDebts->sum('balance');
        $totalDebtsUsd = $companiesWithDebts->sum('balance_usd');

        return view('admin.accounts.financial-position-report', compact(
            'companiesWithDebts',
            'reportDate',
            'totalDebts',
            'totalDebtsUsd'
        ));
    }

    /**
     * تصدير تقرير الموقف المالي إلى PDF
     */
    public function exportFinancialPositionPDF(Request $request)
    {
        $reportDate = $request->date ?? Carbon::now()->format('Y-m-d');

        // جلب جميع الشركات
        $companies = Company::with(['bookings.invoice.invoicePayments'])
            ->orderBy('name')
            ->get();

        $companiesWithDebts = collect();

        foreach ($companies as $company) {
            // حساب الرصيد المستحق حتى تاريخ التقرير
            $totalInvoices = 0;
            $totalPayments = 0;

            $invoices = $company->bookings()
                ->whereHas('invoice', function ($query) use ($reportDate) {
                    $query->whereDate('created_at', '<=', $reportDate);
                })
                ->with('invoice')
                ->get();

            foreach ($invoices as $booking) {
                $invoice = $booking->invoice;
                if ($invoice) {
                    $totalInvoices += $this->calculateInvoiceTotal($invoice);

                    // حساب المدفوعات حتى تاريخ التقرير
                    $payments = $invoice->invoicePayments()
                        ->whereDate('created_at', '<=', $reportDate)
                        ->sum('value');
                    $totalPayments += $payments;
                }
            }

            $balance = $totalInvoices - $totalPayments;

            // إضافة الشركة فقط إذا كان لديها رصيد مستحق (مدين)
            if ($balance > 0) {
                $companiesWithDebts->push([
                    'id' => $company->id,
                    'name' => $company->name,
                    'email' => $company->email,
                    'phone' => $company->phone,
                    'total_invoices' => $totalInvoices,
                    'total_payments' => $totalPayments,
                    'balance' => $balance,
                ]);
            }
        }

        // ترتيب حسب المبلغ المستحق (من الأكبر للأصغر)
        $companiesWithDebts = $companiesWithDebts->sortByDesc('balance')->values();

        // حساب الإجمالي
        $totalDebts = $companiesWithDebts->sum('balance');

        $html = view('admin.accounts.financial-position-report-pdf', compact(
            'companiesWithDebts',
            'reportDate',
            'totalDebts'
        ))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_header' => 6,
            'margin_footer' => 6
        ]);

        $mpdf->WriteHTML($html);

        $fileName = 'تقرير_الموقف_المالي_' . $reportDate . '.pdf';

        return $mpdf->Output($fileName, 'D');
    }

    /**
     * تصدير تقرير الموقف المالي إلى Excel
     */
    public function exportFinancialPositionExcel(Request $request)
    {
        $reportDate = $request->date ?? Carbon::now()->format('Y-m-d');

        // جلب جميع الشركات
        $companies = Company::with(['bookings.invoice.invoicePayments'])
            ->orderBy('name')
            ->get();

        $companiesWithDebts = collect();

        foreach ($companies as $company) {
            // حساب الرصيد المستحق حتى تاريخ التقرير
            $totalInvoices = 0;
            $totalPayments = 0;

            $invoices = $company->bookings()
                ->whereHas('invoice', function ($query) use ($reportDate) {
                    $query->whereDate('created_at', '<=', $reportDate);
                })
                ->with('invoice')
                ->get();

            foreach ($invoices as $booking) {
                $invoice = $booking->invoice;
                if ($invoice) {
                    $totalInvoices += $this->calculateInvoiceTotal($invoice);

                    // حساب المدفوعات حتى تاريخ التقرير
                    $payments = $invoice->invoicePayments()
                        ->whereDate('created_at', '<=', $reportDate)
                        ->sum('value');
                    $totalPayments += $payments;
                }
            }

            $balance = $totalInvoices - $totalPayments;

            // إضافة الشركة فقط إذا كان لديها رصيد مستحق (مدين)
            if ($balance > 0) {
                $companiesWithDebts->push([
                    'id' => $company->id,
                    'name' => $company->name,
                    'email' => $company->email,
                    'phone' => $company->phone,
                    'total_invoices' => $totalInvoices,
                    'total_payments' => $totalPayments,
                    'balance' => $balance,
                ]);
            }
        }

        // ترتيب حسب المبلغ المستحق (من الأكبر للأصغر)
        $companiesWithDebts = $companiesWithDebts->sortByDesc('balance')->values();

        // حساب الإجمالي
        $totalDebts = $companiesWithDebts->sum('balance');

        $fileName = 'تقرير_الموقف_المالي_' . $reportDate . '.xlsx';

        return Excel::download(
            new FinancialPositionExport($companiesWithDebts, $reportDate, $totalDebts),
             $fileName
         );
     }

    /**
     * الموقف المالي للسيارة حتى تاريخ التقرير (نفس منطق كشف الحساب: متبقي كل نقلة)
     */
    private function getCarFinancialPositionUpToDate(Car $car, string $reportDate): array
    {
        $policies = DeliveryPolicy::where('car_id', $car->id)
            ->where('created_at', '<=', $reportDate . ' 23:59:59')
            ->with(['money_transfer', 'settled_money_transfer', 'extraExpenses', 'payingCars'])
            ->get();

        $balance = $policies->sum(fn ($p) => $this->getDeliveryPolicyRemaining($p));

        return [
            'total_cost' => $policies->sum(fn ($p) => (float) ($p->cost ?? 0)),
            'total_net_custody' => $policies->sum(fn ($p) => (float) (($p->money_transfer?->value ?? 0) - ($p->settled_money_transfer?->value ?? 0))),
            'total_extra_expenses' => $policies->sum(fn ($p) => (float) $p->extraExpenses->sum('value')),
            'total_payments' => $policies->sum(fn ($p) => (float) $p->payingCars->sum('value')),
            'balance' => $balance,
        ];
    }

    /**
     * عرض تقرير الموقف المالي - السيارات
     */
    public function carsFinancialPositionReport(Request $request)
    {
        $reportDate = $request->date ?? Carbon::now()->format('Y-m-d');

        $cars = Car::query()
            ->orderBy('car_number')
            ->get();

        $carsWithDebts = collect();

        foreach ($cars as $car) {
            $snapshot = $this->getCarFinancialPositionUpToDate($car, $reportDate);
            $balance = (float) $snapshot['balance'];

            // إظهار أي سيارة لها رصيد غير صفر (موجب = مستحق، سالب = رصيد زائد/دائن)
            if (abs($balance) > 0.0001) {
                $carsWithDebts->push([
                    'car_number' => $car->car_number,
                    'balance' => $balance,
                ]);
            }
        }

        // ترتيب حسب الرصيد (من الأكبر للأصغر)
        $carsWithDebts = $carsWithDebts->sortByDesc('balance')->values();

        // صافي إجمالي الأرصدة (مجموع الموجب والسالب)
        $totalDebts = $carsWithDebts->sum('balance');

        return view('admin.accounts.cars-financial-position-report', compact(
            'carsWithDebts',
            'reportDate',
            'totalDebts'
        ));
    }

    /**
     * عرض تقرير الأرباح والخسائر
     */
    public function profitLossReport(Request $request)
    {
        $fromDate = $request->from ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $toDate = $request->to ?? Carbon::now()->format('Y-m-d');
        $companyId = $request->company_id;

        // جلب الطلبات في الفترة المحددة
        $bookings = Booking::query()
            ->whereHas('invoice', function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59']);
            })
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->with([
                'invoice',
                'company',
                'bookingContainers.expenses.service',
                'bookingContainers.extraExpenses',
                'bookingContainers.delivery_policies'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $reportData = collect();

        foreach ($bookings as $booking) {
            $invoice = $booking->invoice;
            if (!$invoice) {
                continue;
            }

            // حساب التكلفة الفعلية (جميع المصروفات)
            $totalCost = 0;
            $expensesDetails = collect();

            // جمع مصروفات AgentExpense
            foreach ($booking->bookingContainers as $container) {
                foreach ($container->expenses as $expense) {
                    $totalCost += $expense->value ?? 0;
                    $expensesDetails->push([
                        'type' => 'مصروف',
                        'description' => $expense->service->name ?? $expense->notes ?? 'مصروف',
                        'value' => $expense->value ?? 0,
                    ]);
                }

                // جمع المصروفات الإضافية
                foreach ($container->extraExpenses as $extraExpense) {
                    $totalCost += $extraExpense->value ?? 0;
                    $expensesDetails->push([
                        'type' => 'مصروف إضافي',
                        'description' => $extraExpense->name ?? 'مصروف إضافي',
                        'value' => $extraExpense->value ?? 0,
                    ]);
                }
            }

            // حساب سعر الفاتورة
            $invoiceTotal = $this->calculateInvoiceTotal($invoice);

            // حساب الربح/الخسارة
            $profitLoss = $invoiceTotal - $totalCost;

            $reportData->push([
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'invoice_number' => $invoice->invoice_number,
                'company_name' => $booking->company->name,
                'invoice_date' => $invoice->created_at,
                'expenses_details' => $expensesDetails,
                'total_cost' => $totalCost,
                'invoice_total' => $invoiceTotal,
                'profit_loss' => $profitLoss,
            ]);
        }

        // حساب الإجماليات
        $totalCost = $reportData->sum('total_cost');
        $totalRevenue = $reportData->sum('invoice_total');
        $totalProfitLoss = $reportData->sum('profit_loss');

        // جلب قائمة الشركات للفلتر
        $companies = Company::orderBy('name')->get();

        return view('admin.accounts.profit-loss-report', compact(
            'reportData',
            'fromDate',
            'toDate',
            'companyId',
            'companies',
            'totalCost',
            'totalRevenue',
            'totalProfitLoss'
        ));
    }

    /**
     * تصدير تقرير الأرباح والخسائر إلى Excel
     */
    public function exportProfitLossExcel(Request $request)
    {
        $fromDate = $request->from ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $toDate = $request->to ?? Carbon::now()->format('Y-m-d');
        $companyId = $request->company_id;

        // جلب الطلبات في الفترة المحددة
        $bookings = Booking::query()
            ->whereHas('invoice', function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59']);
            })
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->with([
                'invoice',
                'company',
                'bookingContainers.expenses.service',
                'bookingContainers.extraExpenses',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $reportData = collect();

        foreach ($bookings as $booking) {
            $invoice = $booking->invoice;
            if (!$invoice) {
                continue;
            }

            // حساب التكلفة الفعلية
            $totalCost = 0;
            $expensesDetails = collect();

            foreach ($booking->bookingContainers as $container) {
                foreach ($container->expenses as $expense) {
                    $totalCost += $expense->value ?? 0;
                    $expensesDetails->push($expense->service->name ?? $expense->notes ?? 'مصروف');
                }
                foreach ($container->extraExpenses as $extraExpense) {
                    $totalCost += $extraExpense->value ?? 0;
                    $expensesDetails->push($extraExpense->name ?? 'مصروف إضافي');
                }
            }

            $invoiceTotal = $this->calculateInvoiceTotal($invoice);
            $profitLoss = $invoiceTotal - $totalCost;

            $reportData->push([
                'booking_number' => $booking->booking_number,
                'invoice_number' => $invoice->invoice_number,
                'company_name' => $booking->company->name,
                'invoice_date' => $invoice->created_at,
                'expenses_description' => $expensesDetails->implode('، '),
                'total_cost' => $totalCost,
                'invoice_total' => $invoiceTotal,
                'profit_loss' => $profitLoss,
            ]);
        }

        $fileName = 'تقرير_الأرباح_والخسائر_' . $fromDate . '_' . $toDate . '.xlsx';

        return Excel::download(
            new ProfitLossReportExport($reportData, $fromDate, $toDate),
            $fileName
        );
    }

    /**
     * تصدير تقرير الأرباح والخسائر إلى PDF
     */
    public function exportProfitLossPDF(Request $request)
    {
        $fromDate = $request->from ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $toDate = $request->to ?? Carbon::now()->format('Y-m-d');
        $companyId = $request->company_id;

        // جلب الطلبات في الفترة المحددة
        $bookings = Booking::query()
            ->whereHas('invoice', function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59']);
            })
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->with([
                'invoice',
                'company',
                'bookingContainers.expenses.service',
                'bookingContainers.extraExpenses',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $reportData = collect();

        foreach ($bookings as $booking) {
            $invoice = $booking->invoice;
            if (!$invoice) {
                continue;
            }

            $totalCost = 0;
            $expensesDetails = collect();

            foreach ($booking->bookingContainers as $container) {
                foreach ($container->expenses as $expense) {
                    $totalCost += $expense->value ?? 0;
                    $expensesDetails->push([
                        'type' => 'مصروف',
                        'description' => $expense->service->name ?? $expense->notes ?? 'مصروف',
                        'value' => $expense->value ?? 0,
                    ]);
                }
                foreach ($container->extraExpenses as $extraExpense) {
                    $totalCost += $extraExpense->value ?? 0;
                    $expensesDetails->push([
                        'type' => 'مصروف إضافي',
                        'description' => $extraExpense->name ?? 'مصروف إضافي',
                        'value' => $extraExpense->value ?? 0,
                    ]);
                }
            }

            $invoiceTotal = $this->calculateInvoiceTotal($invoice);
            $profitLoss = $invoiceTotal - $totalCost;

            $reportData->push([
                'booking_number' => $booking->booking_number,
                'invoice_number' => $invoice->invoice_number,
                'company_name' => $booking->company->name,
                'invoice_date' => $invoice->created_at,
                'expenses_details' => $expensesDetails,
                'total_cost' => $totalCost,
                'invoice_total' => $invoiceTotal,
                'profit_loss' => $profitLoss,
            ]);
        }

        $totalCost = $reportData->sum('total_cost');
        $totalRevenue = $reportData->sum('invoice_total');
        $totalProfitLoss = $reportData->sum('profit_loss');

        $html = view('admin.accounts.profit-loss-report-pdf', compact(
            'reportData',
            'fromDate',
            'toDate',
            'totalCost',
            'totalRevenue',
            'totalProfitLoss'
        ))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_header' => 6,
            'margin_footer' => 6
        ]);

        $mpdf->WriteHTML($html);

        $fileName = 'تقرير_الأرباح_والخسائر_' . $fromDate . '_' . $toDate . '.pdf';

        return $mpdf->Output($fileName, 'D');
    }
}
