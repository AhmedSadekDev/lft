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
                if ($payment->notes && strpos(ltrim($payment->notes), '{') !== 0) {
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

                $invNo = $payment->invoice ? ($payment->invoice->invoice_number ?? '') : '';
                $bookNo = $payment->invoice && $payment->invoice->booking
                    ? ($payment->invoice->booking->booking_number ?? '')
                    : '';

                return [
                    'id' => $payment->id,
                    'invoice_number' => $invNo !== '' ? $invNo : ($payment->payment_type === 'check' && $payment->check_number ? ('شيك ' . $payment->check_number) : ''),
                    'booking_number' => $bookNo,
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

                // الشيك لا يُسجّل على الحساب البنكي إلا بعد «تم الاستحقاق» من صفحة الشيكات
                if ($request->bank_id && $request->payment_type !== 'check') {
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

            // شيك على فواتير: سجل واحد بالقيمة الإجمالية — لا يُضاف للبنك إلا عند «تم الاستحقاق»
            if ($request->payment_type === 'check') {
                $paymentData = $this->prepareCompanyCheckPaymentData($request, $company, $paymentAmount);
                $payment = InvoicePayment::create($paymentData);
                if ($request->payment_date) {
                    $payment->created_at = $request->payment_date;
                    $payment->save();
                }

                DB::commit();

                return redirect()->route('accounts.statement', $companyId)
                    ->with('success', 'تم تسجيل الشيك بقيمته الإجمالية. سيُضاف المبلغ للحساب البنكي عند الضغط على «تم الاستحقاق» من صفحة الشيكات.');
            }

            // السداد للفواتير (تحويل بنكي)
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
