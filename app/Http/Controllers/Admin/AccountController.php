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
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;

class AccountController extends Controller
{
    use ImagesTrait;
    public function __construct()
    {
        // Clear permission cache to ensure new permissions are recognized
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->middleware('permission:accounts.index')->only('index');
        $this->middleware('permission:accounts.index')->only('statement');
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

        // حساب الرصيد المرحّل من الفواتير السابقة (قبل تاريخ البداية)
        $carriedForwardBalance = $this->calculateCarriedForwardBalance($company, $fromDate);

        // جلب جميع الفواتير في الفترة المحددة
        $invoices = $this->getInvoicesInPeriod($company, $fromDate, $toDate);

        // التأكد من أن $invoices هي collection
        if (!is_a($invoices, \Illuminate\Support\Collection::class)) {
            $invoices = collect($invoices ?? []);
        }

        // حساب إجمالي الفواتير في الفترة
        $totalInvoices = $this->calculateTotalInvoices($invoices);

        // حساب إجمالي السداد في الفترة
        $totalPayments = $this->calculateTotalPayments($company, $fromDate, $toDate);

        // حساب الرصيد النهائي المستحق
        $finalBalance = $carriedForwardBalance + $totalInvoices - $totalPayments;

        // جلب تفاصيل السداد
        $payments = $this->getPaymentsInPeriod($company, $fromDate, $toDate);

        // التأكد من أن $payments هي collection
        if (!is_a($payments, \Illuminate\Support\Collection::class)) {
            $payments = collect($payments ?? []);
        }

        // إنشاء قائمة موحدة بجميع الحركات (فواتير + سداد) مرتبة حسب التاريخ
        $transactions = $this->buildTransactionsList($invoices, $payments, $carriedForwardBalance, $fromDate, $company);

        return view('admin.accounts.statement', compact(
            'company',
            'fromDate',
            'toDate',
            'carriedForwardBalance',
            'invoices',
            'totalInvoices',
            'totalPayments',
            'finalBalance',
            'payments',
            'transactions'
        ));
    }

    /**
     * بناء قائمة موحدة بجميع الحركات (فواتير + سداد)
     */
    private function buildTransactionsList($invoices, $payments, $carriedForwardBalance, $fromDate = null, $company = null)
    {
        $transactions = collect();

        // حساب الرصيد المرحّل بدون الرصيد الافتتاحي
        $openingBalance = $company ? ($company->opening_balance ?? 0) : 0;
        $carriedForwardWithoutOpening = $carriedForwardBalance - $openingBalance;

        // إضافة الرصيد الافتتاحي كأول حركة (إن وجد)
        if ($openingBalance != 0) {
            $startDate = $fromDate ? Carbon::parse($fromDate) : Carbon::now()->startOfYear();
            $transactions->push([
                'date' => $startDate->copy()->subDay(),
                'type' => 'opening_balance',
                'type_label' => 'رصيد افتتاحي',
                'booking_number' => '',
                'invoice_number' => '',
                'previous_debit' => 0, // أول سجل، لا يوجد رصيد سابق
                'previous_credit' => 0, // أول سجل، لا يوجد رصيد سابق
                'discount' => 0,
                'tax' => 0,
                'attachment_statement' => '',
                'transportation' => 0,
                'total' => 0,
                'paid' => 0,
                'notes' => 'رصيد افتتاحي',
                'current_debit' => $openingBalance > 0 ? abs($openingBalance) : 0,
                'current_credit' => $openingBalance < 0 ? abs($openingBalance) : 0,
                'running_balance' => $openingBalance,
            ]);
        }

        // إضافة الرصيد المرحّل كحركة ثانية (إن وجد)
        if ($carriedForwardWithoutOpening != 0) {
            $startDate = $fromDate ? Carbon::parse($fromDate) : Carbon::now()->startOfYear();
            $runningBalance = $openingBalance + $carriedForwardWithoutOpening;

            // حساب الرصيد السابق من السجلات السابقة
            $previousDebit = $transactions->sum('current_debit');
            $previousCredit = $transactions->sum('current_credit');

            $transactions->push([
                'date' => $startDate->copy()->subDay(),
                'type' => 'carried_forward',
                'type_label' => 'رصيد مرحّل',
                'booking_number' => '',
                'invoice_number' => '',
                'previous_debit' => $previousDebit,
                'previous_credit' => $previousCredit,
                'discount' => 0,
                'tax' => 0,
                'attachment_statement' => '',
                'transportation' => 0,
                'total' => 0,
                'paid' => 0,
                'notes' => 'رصيد مرحّل من الفترة السابقة',
                'current_debit' => $carriedForwardWithoutOpening > 0 ? abs($carriedForwardWithoutOpening) : 0,
                'current_credit' => $carriedForwardWithoutOpening < 0 ? abs($carriedForwardWithoutOpening) : 0,
                'running_balance' => $runningBalance,
            ]);
        }

        // إضافة الفواتير (إذا كانت موجودة)
        if ($invoices && is_iterable($invoices)) {
            foreach ($invoices as $invoice) {
                // التحقق من أن $invoice هو array وليس object
                $invoiceId = is_array($invoice) ? ($invoice['id'] ?? null) : ($invoice->id ?? null);
                if (!$invoiceId) continue;

                $invoiceObj = Invoice::find($invoiceId);
                if (!$invoiceObj) continue;

                $transportationTotal = $invoiceObj->transportation_total_before_vat ?? 0;
                $vatAmount = $invoiceObj->value_added_tax_amount ?? 0;
                $discountAmount = $invoiceObj->discount_amount ?? 0;
                $totalInvoice = is_array($invoice) ? ($invoice['total'] ?? 0) : ($invoice->total ?? 0);
                $paidAmount = is_array($invoice) ? ($invoice['paid'] ?? 0) : ($invoice->paid ?? 0);
                $invoiceDate = is_array($invoice) ? ($invoice['date'] ?? now()) : ($invoice->date ?? now());
                $bookingNumber = is_array($invoice) ? ($invoice['booking_number'] ?? '') : ($invoice->booking_number ?? '');
                $invoiceNumber = is_array($invoice) ? ($invoice['invoice_number'] ?? '') : ($invoice->invoice_number ?? '');

                $currentBalance = ($transactions->last()['running_balance'] ?? ($carriedForwardBalance)) + $totalInvoice;

                $transactions->push([
                    'date' => $invoiceDate,
                    'type' => 'invoice',
                    'type_label' => 'فاتورة نقل',
                    'booking_number' => $bookingNumber,
                    'invoice_number' => $invoiceNumber,
                    'previous_debit' => 0, // سيتم حسابه بعد الترتيب
                    'previous_credit' => 0, // سيتم حسابه بعد الترتيب
                    'discount' => $discountAmount,
                    'tax' => $vatAmount,
                    'attachment_statement' => '',
                    'transportation' => $transportationTotal,
                    'total' => $totalInvoice,
                    'paid' => 0,
                    'notes' => '',
                    'current_debit' => $totalInvoice,
                    'current_credit' => 0,
                    'running_balance' => $currentBalance,
                ]);
            }
        }

        // تجميع المدفوعات حسب التاريخ (فقط إذا كانت موجودة)
        $groupedPayments = collect();
        if ($payments && $payments->count() > 0) {
            $groupedPayments = $payments->groupBy(function ($payment) {
                return Carbon::parse($payment->created_at)->format('Y-m-d');
            });
        }

        // إضافة المدفوعات المجمعة
        foreach ($groupedPayments as $date => $dayPayments) {
            $totalPaymentValue = $dayPayments->sum('value');
            $currentBalance = ($transactions->last()['running_balance'] ?? ($carriedForwardBalance)) - $totalPaymentValue;

            // جمع تفاصيل السداد
            $paymentDetails = $dayPayments->map(function ($payment) {
                // الحصول على ملاحظات السداد
                $notes = '';
                if ($payment->notes) {
                    $notes = $payment->notes;
                } elseif ($payment->bank_id) {
                    $bank = Bank::find($payment->bank_id);
                    if ($bank) {
                        $notes = 'تحويل ' . $bank->name;
                    } else {
                        $notes = 'سداد';
                    }
                } else {
                    $notes = 'قام العميل بسداد';
                }

                return [
                    'id' => $payment->id,
                    'invoice_number' => $payment->invoice->invoice_number ?? '',
                    'booking_number' => $payment->invoice->booking->booking_number ?? '',
                    'value' => floatval($payment->value),
                    'notes' => $notes,
                    'payment_type' => $payment->payment_type ?? 'bank_transfer',
                    'bank_name' => $payment->bank ? $payment->bank->name : ($payment->check_bank_name ?? ''),
                    'check_number' => $payment->check_number ?? '',
                    'date' => $payment->created_at,
                ];
            })->values()->toArray(); // تحويل إلى array indexed

            // الحصول على ملاحظات السداد (من أول سداد في اليوم)
            $firstPayment = $dayPayments->first();
            $notes = '';
            if ($firstPayment->notes) {
                $notes = $firstPayment->notes;
            } elseif ($firstPayment->bank_id) {
                $bank = Bank::find($firstPayment->bank_id);
                if ($bank) {
                    $notes = 'تحويل ' . $bank->name;
                } else {
                    $notes = 'سداد';
                }
            } else {
                $notes = 'قام العميل بسداد';
            }

            $transactions->push([
                'date' => Carbon::parse($date),
                'type' => 'payment',
                'type_label' => 'قام العميل بسداد',
                'booking_number' => '',
                'invoice_number' => $dayPayments->count() > 1 ? 'متعدد (' . $dayPayments->count() . ' فاتورة)' : ($firstPayment->invoice->invoice_number ?? ''),
                'previous_debit' => 0, // سيتم حسابه بعد الترتيب
                'previous_credit' => 0, // سيتم حسابه بعد الترتيب
                'discount' => 0,
                'tax' => 0,
                'attachment_statement' => '',
                'transportation' => 0,
                'total' => 0,
                'paid' => $totalPaymentValue,
                'notes' => $notes,
                'current_debit' => 0,
                'current_credit' => $totalPaymentValue,
                'running_balance' => $currentBalance,
                'payment_details' => $paymentDetails, // تفاصيل السداد (تم تحويله إلى array في السطر 259)
                'payment_count' => $dayPayments->count(), // عدد الفواتير
            ]);
        }

        // ترتيب حسب التاريخ (تصاعدي - من الأقدم للأحدث)
        $sortedTransactions = $transactions->sortBy('date')->values();

        // حساب الرصيد السابق لكل سجل بناءً على السجلات السابقة له
        $sortedTransactions = $sortedTransactions->map(function ($transaction, $index) use ($sortedTransactions) {
            if ($index > 0) {
                // حساب مجموع الحساب الحالي من السجلات السابقة
                $previousDebit = $sortedTransactions->take($index)->sum('current_debit');
                $previousCredit = $sortedTransactions->take($index)->sum('current_credit');
                $transaction['previous_debit'] = $previousDebit;
                $transaction['previous_credit'] = $previousCredit;
            }
            return $transaction;
        });

        // ترتيب تنازلي (من الأحدث للأقدم)
        return $sortedTransactions->sortByDesc('date')->values();
    }

    /**
     * تصدير كشف الحساب إلى Excel
     */
    public function exportExcel(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);

        $fromDate = $request->from ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $toDate = $request->to ?? Carbon::now()->format('Y-m-d');

        // حساب الرصيد المرحّل
        $carriedForwardBalance = $this->calculateCarriedForwardBalance($company, $fromDate);

        // جلب الفواتير
        $invoices = $this->getInvoicesInPeriod($company, $fromDate, $toDate);
        $totalInvoices = $this->calculateTotalInvoices($invoices);

        // جلب المدفوعات
        $totalPayments = $this->calculateTotalPayments($company, $fromDate, $toDate);
        $payments = $this->getPaymentsInPeriod($company, $fromDate, $toDate);

        // حساب الرصيد النهائي
        $finalBalance = $carriedForwardBalance + $totalInvoices - $totalPayments;

        // إنشاء قائمة موحدة بجميع الحركات
        $transactions = $this->buildTransactionsList($invoices, $payments, $carriedForwardBalance, $fromDate, $company);

        // ترتيب تصاعدي (ASC) للتصدير
        $transactions = $transactions->sortBy('date')->values();

        $fileName = 'كشف_حساب_' . $company->name . '_' . $fromDate . '_' . $toDate . '.xlsx';

        return Excel::download(
            new AccountStatementExport($company, $fromDate, $toDate, $carriedForwardBalance, $totalInvoices, $totalPayments, $finalBalance, $invoices, $payments, $transactions ?? collect()),
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

        // حساب الرصيد المرحّل
        $carriedForwardBalance = $this->calculateCarriedForwardBalance($company, $fromDate);

        // جلب الفواتير
        $invoices = $this->getInvoicesInPeriod($company, $fromDate, $toDate);
        $totalInvoices = $this->calculateTotalInvoices($invoices);

        // جلب المدفوعات
        $totalPayments = $this->calculateTotalPayments($company, $fromDate, $toDate);
        $payments = $this->getPaymentsInPeriod($company, $fromDate, $toDate);

        // حساب الرصيد النهائي
        $finalBalance = $carriedForwardBalance + $totalInvoices - $totalPayments;

        // إنشاء قائمة موحدة بجميع الحركات
        $transactions = $this->buildTransactionsList($invoices, $payments, $carriedForwardBalance, $fromDate, $company);

        // ترتيب تصاعدي (ASC) للتصدير
        $transactions = $transactions->sortBy('date')->values();

        $html = view('admin.accounts.statement-pdf', compact(
            'company',
            'fromDate',
            'toDate',
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

        $fileName = 'كشف_حساب_' . $company->name . '_' . $fromDate . '_' . $toDate . '.pdf';

        return $mpdf->Output($fileName, 'D');
    }

    /**
     * عرض صفحة السداد
     */
    public function showPaymentForm($companyId)
    {
        $company = Company::findOrFail($companyId);
        $banks = Bank::orderBy('name')->get();

        // حساب الرصيد المستحق الحالي
        $currentBalance = $this->calculateCurrentBalance($company);

        // جلب الفواتير غير المسددة بالكامل
        $unpaidInvoices = collect();
        $bookings = $company->bookings()
            ->whereHas('invoice')
            ->with('invoice')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($bookings as $booking) {
            $invoice = $booking->invoice;
            if (!$invoice) continue;

            $invoiceTotal = $this->calculateInvoiceTotal($invoice);
            $paidAmount = $this->calculatePaidAmount($invoice);
            $remainingAmount = $invoiceTotal - $paidAmount;

            if ($remainingAmount > 0) {
                $unpaidInvoices->push([
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'booking_number' => $booking->booking_number ?? '-',
                    'date' => $invoice->created_at,
                    'total' => $invoiceTotal,
                    'paid' => $paidAmount,
                    'remaining' => $remainingAmount,
                ]);
            }
        }

        return view('admin.accounts.payment', compact('company', 'banks', 'currentBalance', 'unpaidInvoices'));
    }

    /**
     * معالجة السداد
     */
    public function processPayment(Request $request, $companyId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
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
            'invoice_ids' => 'nullable|string'
        ]);

        $company = Company::findOrFail($companyId);

        // استخدام قيمة الشيك إذا كان نوع السداد شيك
        $paymentAmount = $request->payment_type === 'check' ? $request->check_value : $request->amount;

        DB::beginTransaction();

        try {
            // إذا كان السداد للرصيد الافتتاحي
            if ($request->payment_target === 'opening_balance') {
                $openingBalance = $company->opening_balance ?? 0;

                if ($openingBalance <= 0) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'لا يوجد رصيد افتتاحي لسداده');
                }

                if ($paymentAmount > $openingBalance) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'المبلغ أكبر من الرصيد الافتتاحي. الرصيد الافتتاحي: ' . number_format($openingBalance, 2) . ' جنيه');
                }

                // خصم المبلغ من الرصيد الافتتاحي
                $company->opening_balance = $openingBalance - $paymentAmount;
                $company->save();

                // إذا كان السداد مرتبط ببنك، تحديث رصيد البنك فقط (بدون الخزنة)
                if ($request->bank_id) {
                    $bank = Bank::findOrFail($request->bank_id);
                    
                    // إضافة المبلغ إلى البنك
                    $bank->amount = ($bank->amount ?? 0) + $paymentAmount;
                    $bank->save();

                    // تسجيل معاملة البنك
                    BankTrnsaction::create([
                        'bank_id' => $bank->id,
                        'company_id' => $company->id,
                        'user_id' => auth()->id(),
                        'name' => 'سداد الرصيد الافتتاحي - ' . $company->name,
                        'type' => 1, // 1 = إيداع
                        'amount' => $paymentAmount,
                        'date' => $request->payment_date ?? now()->format('Y-m-d'),
                    ]);
                }

                DB::commit();

                return redirect()->route('accounts.statement', $companyId)
                    ->with('success', 'تم سداد الرصيد الافتتاحي بنجاح. المبلغ: ' . number_format($paymentAmount, 2) . ' جنيه');
            }

            // السداد للفواتير (الكود الأصلي)
            $remainingPayment = $paymentAmount;
            $processedInvoices = [];
            $invoiceCount = 0;
            $bankTransactionId = null;

            // إذا كان السداد مرتبط ببنك، تحديث رصيد البنك فقط (بدون الخزنة)
            if ($request->bank_id) {
                $bank = Bank::findOrFail($request->bank_id);
                
                // إضافة المبلغ إلى البنك
                $bank->amount = ($bank->amount ?? 0) + $paymentAmount;
                $bank->save();

                // تسجيل معاملة البنك
                $bankTransaction = BankTrnsaction::create([
                    'bank_id' => $bank->id,
                    'company_id' => $company->id,
                    'user_id' => auth()->id(),
                    'name' => 'سداد فواتير - ' . $company->name,
                    'type' => 1, // 1 = إيداع
                    'amount' => $paymentAmount,
                    'date' => $request->payment_date ?? now()->format('Y-m-d'),
                ]);

                $bankTransactionId = $bankTransaction->id;
            }

            // إذا تم تحديد فواتير محددة
            if ($request->invoice_ids) {
                $invoiceIds = explode(',', $request->invoice_ids);
                $invoiceIds = array_filter($invoiceIds);

                if (count($invoiceIds) > 0) {
                    // جلب الفواتير المحددة
                    $invoices = Invoice::whereIn('id', $invoiceIds)
                        ->whereHas('booking', function($query) use ($companyId) {
                            $query->where('company_id', $companyId);
                        })
                        ->with('booking')
                        ->orderBy('created_at', 'asc')
                        ->get();

                    foreach ($invoices as $invoice) {
                        if ($remainingPayment <= 0) {
                            break;
                        }

                        // حساب المبلغ المستحق للفاتورة
                        $invoiceTotal = $this->calculateInvoiceTotal($invoice);
                        $paidAmount = $this->calculatePaidAmount($invoice);
                        $remainingAmount = $invoiceTotal - $paidAmount;

                        if ($remainingAmount > 0) {
                            // تحديد المبلغ المراد سداده من هذه الفاتورة
                            $paymentValue = min($remainingAmount, $remainingPayment);

                            // تسجيل السداد
                            $paymentData = $this->preparePaymentData($request, $company, $invoice, $paymentValue, $bankTransactionId);

                            $payment = InvoicePayment::create($paymentData);

                            // تحديث التاريخ إذا كان متوفر
                            if ($request->payment_date) {
                                $payment->created_at = $request->payment_date;
                                $payment->save();
                            }

                            $processedInvoices[] = $invoice->invoice_number;
                            $invoiceCount++;
                            $remainingPayment -= $paymentValue;
                        }
                    }
                }
            } else {
                // توزيع المبلغ على جميع الفواتير (من الأقدم إلى الأحدث)
                $bookings = $company->bookings()
                    ->whereHas('invoice')
                    ->with('invoice')
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($bookings as $booking) {
                    if ($remainingPayment <= 0) {
                        break;
                    }

                    $invoice = $booking->invoice;
                    if (!$invoice) {
                        continue;
                    }

                    // حساب المبلغ المستحق للفاتورة
                    $invoiceTotal = $this->calculateInvoiceTotal($invoice);
                    $paidAmount = $invoice->invoicePayments()->sum('value');
                    $remainingAmount = $invoiceTotal - $paidAmount;

                    if ($remainingAmount > 0) {
                        // تحديد المبلغ المراد سداده من هذه الفاتورة
                        $paymentValue = min($remainingAmount, $remainingPayment);

                        // تسجيل السداد
                        $paymentData = $this->preparePaymentData($request, $company, $invoice, $paymentValue, $bankTransactionId);

                        $payment = InvoicePayment::create($paymentData);

                        // تحديث التاريخ إذا كان متوفر
                        if ($request->payment_date) {
                            $payment->created_at = $request->payment_date;
                            $payment->save();
                        }

                        $processedInvoices[] = $invoice->invoice_number;
                        $invoiceCount++;
                        $remainingPayment -= $paymentValue;
                    }
                }
            }

            DB::commit();

            $message = 'تم تسجيل السداد بنجاح';
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
     * حذف سداد مسجل وإرجاعه كمبلغ مستحق مرة أخرى
     */
    public function destroyPayment(Request $request, $companyId, $paymentId)
    {
        $company = Company::findOrFail($companyId);

        $payment = InvoicePayment::with(['invoice.booking', 'bank'])
            ->where('id', $paymentId)
            ->whereHas('invoice.booking', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->firstOrFail();

        DB::beginTransaction();

        try {
            // عند حذف سداد من كشف الحساب: خصم المبلغ من البنك وحذف/تحديث عملية البنك المرتبطة
            if ($payment->bank_id) {
                $bank = $payment->bank;

                if ($bank) {
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
            ->whereHas('invoice.booking', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
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
     * تحديد الشيك كمستحق (خصم القيمة من حساب الشركة)
     */
    public function markCheckAsPaid($paymentId)
    {
        $payment = InvoicePayment::findOrFail($paymentId);

        if ($payment->payment_type !== 'check') {
            return redirect()->back()->with('error', 'هذا السجل ليس شيك');
        }

        if ($payment->check_paid_at) {
            return redirect()->back()->with('error', 'تم استحقاق هذا الشيك مسبقاً');
        }

        DB::beginTransaction();

        try {
            // تحديث تاريخ الاستحقاق
            $payment->check_paid_at = now();
            $payment->save();

            // خصم القيمة من حساب الشركة (إنشاء سداد تلقائي)
            $company = $payment->company;
            if ($company && $payment->invoice) {
                // إنشاء سداد جديد للفاتورة
                InvoicePayment::create([
                    'invoice_id' => $payment->invoice_id,
                    'company_id' => $company->id,
                    'value' => $payment->value,
                    'payment_type' => 'bank_transfer',
                    'user_id' => auth()->id(),
                    'notes' => 'استحقاق شيك رقم: ' . $payment->check_number,
                    'image' => '',
                    'created_at' => now(),
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'تم استحقاق الشيك وخصم القيمة من حساب الشركة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ أثناء استحقاق الشيك: ' . $e->getMessage());
        }
    }

    /**
     * حساب الرصيد المرحّل من الفواتير السابقة
     */
    private function calculateCarriedForwardBalance($company, $fromDate)
    {
        $totalInvoices = 0;
        $totalPayments = 0;

        // حساب إجمالي الفواتير قبل تاريخ البداية
        $invoices = $company->bookings()
            ->whereHas('invoice', function ($query) use ($fromDate) {
                $query->whereDate('created_at', '<', $fromDate);
            })
            ->with('invoice')
            ->get();

        foreach ($invoices as $booking) {
            $invoice = $booking->invoice;
            if ($invoice) {
                $totalInvoices += $this->calculateInvoiceTotal($invoice);
            }
        }

        // حساب إجمالي السداد قبل تاريخ البداية (استبعاد الشيكات غير المستحقة)
        $payments = InvoicePayment::whereHas('invoice.booking', function ($query) use ($company, $fromDate) {
            $query->where('company_id', $company->id)
                  ->whereDate('invoice_payments.created_at', '<', $fromDate);
        })
        ->where(function($query) {
            $query->where('payment_type', '!=', 'check')
                  ->orWhere(function($q) {
                      $q->where('payment_type', 'check')
                        ->whereNotNull('check_paid_at');
                  });
        })
        ->sum('value');

        // إضافة الرصيد الافتتاحي
        $openingBalance = $company->opening_balance ?? 0;

        return $openingBalance + $totalInvoices - $totalPayments;
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
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'booking_number' => $booking->booking_number,
                    'date' => $invoice->created_at,
                    'total' => $this->calculateInvoiceTotal($invoice),
                    'paid' => $this->calculatePaidAmount($invoice),
                    'remaining' => $this->calculateInvoiceTotal($invoice) - $this->calculatePaidAmount($invoice),
                ];
            });
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
        return InvoicePayment::whereHas('invoice.booking', function ($query) use ($company, $fromDate, $toDate) {
            $query->where('company_id', $company->id)
                  ->whereBetween('invoice_payments.created_at', [$fromDate, $toDate . ' 23:59:59']);
        })
        ->where(function($query) {
            $query->where('payment_type', '!=', 'check')
                  ->orWhere(function($q) {
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
        return InvoicePayment::whereHas('invoice.booking', function ($query) use ($company) {
            $query->where('company_id', $company->id);
        })
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
     * حساب المدفوعات الفعلية (استبعاد الشيكات غير المستحقة)
     */
    private function calculatePaidAmount($invoice)
    {
        return $invoice->invoicePayments()
            ->where(function($query) {
                $query->where('payment_type', '!=', 'check')
                      ->orWhere(function($q) {
                          $q->where('payment_type', 'check')
                            ->whereNotNull('check_paid_at');
                      });
            })
            ->sum('value');
    }

    /**
     * حساب الرصيد المستحق الحالي
     */
    private function calculateCurrentBalance($company)
    {
        $totalInvoices = 0;
        $totalPayments = 0;

        $invoices = $company->bookings()
            ->whereHas('invoice')
            ->with('invoice')
            ->get();

        foreach ($invoices as $booking) {
            $invoice = $booking->invoice;
            if ($invoice) {
                $totalInvoices += $this->calculateInvoiceTotal($invoice);
                $totalPayments += $this->calculatePaidAmount($invoice);
            }
        }

        return $totalInvoices - $totalPayments;
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
        $runningBalance = $carriedForwardBalance;

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
            ]);
        }

        $deliveryPolicies = DeliveryPolicy::where('car_id', $car->id)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->with(['money_transfer', 'settled_money_transfer', 'booking_containers.departure', 'booking_containers.loading', 'booking_containers.aging', 'extraExpenses', 'payingCars'])
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($deliveryPolicies as $policy) {
            $cost = (float) ($policy->cost ?? 0);
            $custodyGiven = (float) ($policy->money_transfer?->value ?? 0);
            $custodySettled = (float) ($policy->settled_money_transfer?->value ?? 0);
            $custodyAmount = $custodyGiven - $custodySettled;
            $containers = $policy->booking_containers;
            $firstContainer = $containers->first();
            $container = $firstContainer ? BookingContainer::with(['departure', 'loading', 'aging'])->find($firstContainer->id) : null;

            // صف النقلة: تكلفة وعهدة (الرصيد += تكلفة - عهدة)
            $runningBalance += $cost - $custodyAmount;
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
                'running_total' => $runningBalance,
                'running_balance' => $runningBalance,
            ]);

            // مصروفات إضافية للنقلة (الرصيد += قيمة المصروف)
            foreach ($policy->extraExpenses as $extraExpense) {
                $runningBalance += $extraExpense->value;
                $container = null;
                if ($extraExpense->booking_container_id) {
                    $container = BookingContainer::with(['departure', 'loading', 'aging'])->find($extraExpense->booking_container_id);
                } elseif ($firstContainer) {
                    $container = BookingContainer::with(['departure', 'loading', 'aging'])->find($firstContainer->id);
                }
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
                    'running_total' => $runningBalance,
                    'running_balance' => $runningBalance,
                ]);
            }

            // دفعات النقلة (الرصيد -= قيمة الدفعة)
            foreach ($policy->payingCars as $payment) {
                $runningBalance -= $payment->value;
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
                    'total2' => $payment->value,
                    'debit_credit' => 'دائن',
                    'running_total' => $runningBalance,
                    'running_balance' => $runningBalance,
                ]);
            }
        }

        return $transactions->sortBy('date')->values();
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
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'shipment_ids' => 'required|string', // comma-separated IDs
        ]);

        $car = Car::findOrFail($carId);
        $vault = Vault::first();

        if (!$vault) {
            return redirect()->back()->with('error', 'لا توجد خزنة في النظام');
        }

        $shipmentIds = array_filter(explode(',', $request->shipment_ids));

        if (empty($shipmentIds)) {
            return redirect()->back()->with('error', 'يجب تحديد نقلات على الأقل');
        }

        DB::beginTransaction();

        try {
            $totalPaymentAmount = 0;
            $processedShipments = [];
            $processedShipmentAmounts = [];

            foreach ($shipmentIds as $shipmentId) {
                $policy = DeliveryPolicy::with(['money_transfer', 'settled_money_transfer', 'extraExpenses', 'payingCars'])
                    ->findOrFail($shipmentId);

                if ($policy->car_id != $carId) {
                    continue; // Skip if not for this car
                }

                $cost = $policy->cost ?? 0;
                $custodyGiven = (float) ($policy->money_transfer?->value ?? 0);
                $custodySettled = (float) ($policy->settled_money_transfer?->value ?? 0);
                $financialCustody = $custodyGiven - $custodySettled;
                $extraExpenses = $policy->extraExpenses->sum('value') ?? 0;
                $paidTotal = $policy->payingCars->sum('value') ?? 0;

                // حساب المتبقي (صافي العهدة)
                $remaining = $cost
                    ? $cost - $financialCustody + $extraExpenses - $paidTotal
                    : $extraExpenses + $paidTotal - $financialCustody;

                if ($remaining > 0) {
                    // إنشاء سداد للنقلة
                    $paymentData = [
                        'delivery_policy_id' => $policy->id,
                        'car_id' => $car->id,
                        'value' => $remaining,
                        'user_id' => auth()->id(),
                    ];

                    if ($request->hasFile('image')) {
                        $imageName = time() . '_car_payment_' . $policy->id . '.' . $request->image->extension();
                        $this->uploadImage($request->image, $imageName, 'banks');
                        $paymentData['image'] = 'Admin/images/banks/' . $imageName;
                    }

                    $payingCar = Payingcar::create($paymentData);

                    // تحديث التاريخ إذا كان متوفر
                    if ($request->payment_date) {
                        $payingCar->created_at = $request->payment_date;
                        $payingCar->save();
                    }

                    // تسجيل معاملة MoneyTransfer
                    MoneyTransfer::create([
                        'value' => $remaining,
                        'transfered_type' => 'App\Models\Payingcar',
                        'transfered_id' => $payingCar->id,
                        'transferer_type' => 'App\Models\User',
                        'transferer_id' => auth()->id(),
                        'type' => 7, // carPayment
                    ]);

                    // خصم من الخزنة
                    $vault->amount = ($vault->amount ?? 0) - $remaining;
                    $vault->save();

                    // تسجيل معاملة الخزنة
                    VaultTransaction::create([
                        'name' => 'سداد نقلة - ' . $car->car_number,
                        'amount' => $remaining,
                        'type' => 0, // منصرف
                    ]);

                    $totalPaymentAmount += $remaining;
                    $processedShipments[] = $policy->id;
                    $processedShipmentAmounts[] = $remaining;
                }
            }

            if ($totalPaymentAmount == 0) {
                DB::rollBack();
                return redirect()->back()->with('error', 'لا توجد مبالغ مستحقة في النقلات المحددة');
            }

            DB::commit();

            $message = 'تم تسجيل السداد بنجاح. المبلغ الإجمالي: ' . number_format($totalPaymentAmount, 2) . ' جنيه (' . count($processedShipments) . ' نقلة)';

            return redirect()->route('accounts.car.payment', $carId)
                ->with('success', $message)
                ->with('processed_shipments', $processedShipments)
                ->with('processed_shipment_amounts', $processedShipmentAmounts);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ أثناء تسجيل السداد: ' . $e->getMessage());
        }
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

        return view('admin.accounts.financial-position-report', compact(
            'companiesWithDebts',
            'reportDate',
            'totalDebts'
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
            $balance = $snapshot['balance'];

            // إضافة السيارة فقط إذا كان لديها رصيد مستحق (مدين)
            if ($balance > 0) {
                $carsWithDebts->push([
                    'id' => $car->id,
                    'car_number' => $car->car_number,
                    'total_cost' => $snapshot['total_cost'],
                    'total_net_custody' => $snapshot['total_net_custody'],
                    'total_extra_expenses' => $snapshot['total_extra_expenses'],
                    'total_payments' => $snapshot['total_payments'],
                    'balance' => $balance,
                ]);
            }
        }

        // ترتيب حسب المبلغ المستحق (من الأكبر للأصغر)
        $carsWithDebts = $carsWithDebts->sortByDesc('balance')->values();

        // حساب الإجمالي
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
