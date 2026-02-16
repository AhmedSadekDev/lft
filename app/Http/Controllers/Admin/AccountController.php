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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\AccountStatementExport;
use App\Exports\CarStatementExport;
use App\Exports\FinancialPositionExport;
use App\Exports\ProfitLossReportExport;
use App\Models\BookingContrainerExtraCosts;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;

class AccountController extends Controller
{
    public function __construct()
    {
        // Clear permission cache to ensure new permissions are recognized
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->middleware('permission:accounts.index')->only('index');
        $this->middleware('permission:accounts.index')->only('statement');
        $this->middleware('permission:accounts.create')->only(['showPaymentForm', 'processPayment']);
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

                $invoiceObj = \App\Models\Invoice::find($invoiceId);
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
                    $bank = \App\Models\Bank::find($payment->bank_id);
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
                    'value' => $payment->value,
                    'notes' => $notes,
                    'payment_type' => $payment->payment_type,
                    'bank_name' => $payment->bank ? $payment->bank->name : ($payment->check_bank_name ?? ''),
                    'check_number' => $payment->check_number ?? '',
                    'date' => $payment->created_at,
                ];
            });

            // الحصول على ملاحظات السداد (من أول سداد في اليوم)
            $firstPayment = $dayPayments->first();
            $notes = '';
            if ($firstPayment->notes) {
                $notes = $firstPayment->notes;
            } elseif ($firstPayment->bank_id) {
                $bank = \App\Models\Bank::find($firstPayment->bank_id);
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
                'payment_details' => $paymentDetails, // تفاصيل السداد
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
        $banks = \App\Models\Bank::orderBy('name')->get();

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
            'bank_id' => 'required_if:payment_type,bank_transfer|nullable|exists:banks,id',
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
                    return redirect()->back()->with('error', 'لا يوجد رصيد افتتاحي لسداده');
                }

                if ($paymentAmount > $openingBalance) {
                    return redirect()->back()->with('error', 'المبلغ أكبر من الرصيد الافتتاحي. الرصيد الافتتاحي: ' . number_format($openingBalance, 2) . ' جنيه');
                }

                // خصم المبلغ من الرصيد الافتتاحي
                $company->opening_balance = $openingBalance - $paymentAmount;
                $company->save();

                // تسجيل السداد في log (اختياري - يمكن إضافة جدول منفصل لسداد الرصيد الافتتاحي)
                // يمكن إضافة جدول opening_balance_payments إذا لزم الأمر

                DB::commit();

                return redirect()->route('accounts.statement', $companyId)
                    ->with('success', 'تم سداد الرصيد الافتتاحي بنجاح. المبلغ: ' . number_format($paymentAmount, 2) . ' جنيه');
            }

            // السداد للفواتير (الكود الأصلي)
            $remainingPayment = $paymentAmount;
            $processedInvoices = [];
            $invoiceCount = 0;

            // إذا تم تحديد فواتير محددة
            if ($request->invoice_ids) {
                $invoiceIds = explode(',', $request->invoice_ids);
                $invoiceIds = array_filter($invoiceIds);

                if (count($invoiceIds) > 0) {
                    // جلب الفواتير المحددة
                    $invoices = \App\Models\Invoice::whereIn('id', $invoiceIds)
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
                            $paymentData = $this->preparePaymentData($request, $company, $invoice, $paymentValue);

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
                        $paymentData = $this->preparePaymentData($request, $company, $invoice, $paymentValue);

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
     * إعداد بيانات السداد
     */
    private function preparePaymentData($request, $company, $invoice, $paymentValue)
    {
        $paymentData = [
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'value' => $paymentValue,
            'user_id' => auth()->id(),
            'payment_type' => $request->payment_type,
        ];

        // إضافة بيانات البنك للتحويل البنكي
        if ($request->payment_type === 'bank_transfer') {
            $paymentData['bank_id'] = $request->bank_id;
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
     * عرض كشف حساب لسيارة معينة
     */
    public function carStatement(Request $request, $carId)
    {
        $car = Car::findOrFail($carId);

        $fromDate = $request->from ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $toDate = $request->to ?? Carbon::now()->format('Y-m-d');

        // حساب الرصيد المرحّل من الفترة السابقة
        $carriedForwardBalance = $this->calculateCarCarriedForwardBalance($car, $fromDate);

        // جلب جميع الحركات في الفترة المحددة
        $transactions = $this->buildCarTransactionsList($car, $fromDate, $toDate, $carriedForwardBalance);

        // حساب الإجماليات
        $totalValue = $transactions->where('type', '!=', 'payment')->sum('value');
        $totalCustody = $transactions->where('type', '!=', 'payment')->sum('custody');
        $totalPayments = $transactions->where('type', 'payment')->sum('value');
        $finalBalance = ($transactions->last()['running_balance'] ?? $carriedForwardBalance);

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

        // حساب الرصيد المرحّل
        $carriedForwardBalance = $this->calculateCarCarriedForwardBalance($car, $fromDate);

        // بناء قائمة الحركات
        $transactions = $this->buildCarTransactionsList($car, $fromDate, $toDate, $carriedForwardBalance);

        // حساب الإجماليات
        $totalValue = $transactions->where('type', '!=', 'payment')->sum('value');
        $totalCustody = $transactions->where('type', '!=', 'payment')->sum('custody');
        $totalPayments = $transactions->where('type', 'payment')->sum('value');
        $finalBalance = ($transactions->last()['running_balance'] ?? $carriedForwardBalance);

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

        // حساب الرصيد المرحّل
        $carriedForwardBalance = $this->calculateCarCarriedForwardBalance($car, $fromDate);

        // بناء قائمة الحركات
        $transactions = $this->buildCarTransactionsList($car, $fromDate, $toDate, $carriedForwardBalance);

        // حساب الإجماليات
        $totalValue = $transactions->where('type', '!=', 'payment')->sum('value');
        $totalCustody = $transactions->where('type', '!=', 'payment')->sum('custody');
        $totalPayments = $transactions->where('type', 'payment')->sum('value');
        $finalBalance = ($transactions->last()['running_balance'] ?? $carriedForwardBalance);

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
     * حساب الرصيد المرحّل للسيارة
     */
    private function calculateCarCarriedForwardBalance($car, $fromDate)
    {
        // حساب إجمالي العهدة من delivery policies قبل تاريخ البداية
        $totalCustody = DeliveryPolicy::where('car_id', $car->id)
            ->whereHas('money_transfer', function ($query) {
                $query->where('type', MoneyTransfer::deliveryPolicy);
            })
            ->whereDate('created_at', '<', $fromDate)
            ->with('money_transfer')
            ->get()
            ->sum(function ($policy) {
                return $policy->money_transfer->value ?? 0;
            });

        // حساب إجمالي المصاريف قبل تاريخ البداية
        $totalExpenses = AgentExpense::whereHas('bookingContainer.delivery_policies', function ($query) use ($car) {
            $query->where('car_id', $car->id);
        })->whereDate('agent_expenses.created_at', '<', $fromDate)->sum('value');

        // حساب إجمالي الدفعات قبل تاريخ البداية
        $totalPayments = Payingcar::where('car_id', $car->id)
            ->whereDate('created_at', '<', $fromDate)
            ->sum('value');

        // الرصيد المرحّل = العهدة - المصاريف - الدفعات
        return $totalCustody - $totalExpenses - $totalPayments;
    }

    /**
     * بناء قائمة الحركات لكشف حساب السيارة
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
            ]);
        }

        // جلب delivery policies في الفترة
        $deliveryPolicies = DeliveryPolicy::where('car_id', $car->id)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->with(['money_transfer', 'booking_containers.departure', 'booking_containers.loading', 'booking_containers.aging', 'extraExpenses', 'car', 'driver'])
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($deliveryPolicies as $policy) {
            $custodyAmount = $policy->money_transfer->value ?? 0;
            $runningBalance = ($transactions->last()['running_balance'] ?? $carriedForwardBalance) + $custodyAmount;

            // جلب الحاويات المرتبطة
            $containers = $policy->booking_containers;
            foreach ($containers as $container) {
                $transactions->push([
                    'date' => $policy->created_at,
                    'type' => 'delivery_policy',
                    'type_label' => 'عهدة',
                    'previous_balance' => 0,
                    'service' => 'نقل',
                    'description' => $policy->address ?? '',
                    'container_no' => $container->container_no ?? $container->sail_of_number ?? '',
                    'departure' => $container->departure ? $container->departure->title : '',
                    'destination' => $container->loading ? $container->loading->title : '',
                    'aging' => $container->aging ? $container->aging->title : '',
                    'value' => $container->price ?? 0,
                    'custody' => $custodyAmount / ($containers->count() > 0 ? $containers->count() : 1),
                    'total1' => $container->price ?? 0,
                    'total2' => $custodyAmount / ($containers->count() > 0 ? $containers->count() : 1),
                    'debit_credit' => 'مدين',
                    'running_total' => $runningBalance,
                    'running_balance' => $runningBalance,
                ]);
            }

            // إضافة المصاريف الإضافية المرتبطة بـ delivery policy
            $extraExpenses = $policy->extraExpenses;
            foreach ($extraExpenses as $extraExpense) {
                $runningBalance = ($transactions->last()['running_balance'] ?? $carriedForwardBalance) - $extraExpense->value;

                // جلب الحاوية المرتبطة (إن وجدت)
                $container = null;
                if ($extraExpense->booking_container_id) {
                    $container = \App\Models\BookingContainer::with(['departure', 'loading', 'aging'])
                        ->find($extraExpense->booking_container_id);
                } else {
                    // إذا لم تكن مرتبطة بحاوية، استخدم أول حاوية من delivery policy
                    $firstContainer = $containers->first();
                    if ($firstContainer) {
                        $container = \App\Models\BookingContainer::with(['departure', 'loading', 'aging'])
                            ->find($firstContainer->id);
                    }
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
                    'debit_credit' => 'دائن',
                    'running_total' => $runningBalance,
                    'running_balance' => $runningBalance,
                ]);
            }
        }

        // جلب المصاريف في الفترة
        $expenses = AgentExpense::where(function ($query) use ($car, $fromDate, $toDate) {
            $query->whereHas('delivery_policy', function ($q) use ($car) {
                $q->where('car_id', $car->id);
            })
            ->orWhereHas('bookingContainer.delivery_policies', function ($q) use ($car) {
                $q->where('car_id', $car->id);
            });
        })
        ->whereBetween('agent_expenses.created_at', [$fromDate, $toDate . ' 23:59:59'])
        ->with(['bookingContainer.departure', 'bookingContainer.loading', 'bookingContainer.aging', 'service', 'delivery_policy'])
        ->orderBy('agent_expenses.created_at', 'asc')
        ->get();

        foreach ($expenses as $expense) {
            $runningBalance = ($transactions->last()['running_balance'] ?? $carriedForwardBalance) - $expense->value;

            $container = $expense->bookingContainer;
            $transactions->push([
                'date' => $expense->created_at,
                'type' => 'expense',
                'type_label' => 'مصروف',
                'previous_balance' => 0,
                'service' => $expense->service->name ?? 'مصروف',
                'description' => $expense->notes ?? '',
                'container_no' => $container ? ($container->container_no ?? $container->sail_of_number ?? '') : '',
                'departure' => $container && $container->departure ? $container->departure->title : '',
                'destination' => $container && $container->loading ? $container->loading->title : '',
                'aging' => $container && $container->aging ? $container->aging->title : '',
                'value' => 0,
                'custody' => 0,
                'total1' => 0,
                'total2' => $expense->value,
                'debit_credit' => 'دائن',
                'running_total' => $runningBalance,
                'running_balance' => $runningBalance,
            ]);
        }

        // جلب المصاريف الإضافية المرتبطة بالحاويات مباشرة (وليس delivery policy)
        $extraExpensesFromContainers = BookingContrainerExtraCosts::whereHas('booking_container.delivery_policies', function ($query) use ($car) {
            $query->where('car_id', $car->id);
        })
        ->whereNull('delivery_policy_id')
        ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
        ->with(['booking_container.departure', 'booking_container.loading', 'booking_container.aging'])
        ->orderBy('created_at', 'asc')
        ->get();

        foreach ($extraExpensesFromContainers as $extraExpense) {
            $runningBalance = ($transactions->last()['running_balance'] ?? $carriedForwardBalance) - $extraExpense->value;

            $container = $extraExpense->booking_container;
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
                'debit_credit' => 'دائن',
                'running_total' => $runningBalance,
                'running_balance' => $runningBalance,
            ]);
        }

        // جلب الدفعات في الفترة
        $payments = Payingcar::where('car_id', $car->id)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->with('delivery_policy')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($payments as $payment) {
            $runningBalance = ($transactions->last()['running_balance'] ?? $carriedForwardBalance) - $payment->value;

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

        // ترتيب حسب التاريخ
        return $transactions->sortBy('date')->values();
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
     * عرض تقرير الموقف المالي - السيارات
     */
    public function carsFinancialPositionReport(Request $request)
    {
        $reportDate = $request->date ?? Carbon::now()->format('Y-m-d');

        // جلب جميع السيارات
        $cars = Car::with(['deliveryPolicies', 'payingcars'])
            ->orderBy('car_number')
            ->get();

        $carsWithDebts = collect();

        foreach ($cars as $car) {
            // حساب الرصيد المستحق حتى تاريخ التقرير
            $totalCost = $car->deliveryPolicies()
                ->whereDate('created_at', '<=', $reportDate)
                ->sum('cost');

            $totalPaid = $car->payingcars()
                ->whereDate('created_at', '<=', $reportDate)
                ->sum('value');

            $balance = $totalCost - $totalPaid;

            // إضافة السيارة فقط إذا كان لديها رصيد مستحق (مدين)
            if ($balance > 0) {
                $carsWithDebts->push([
                    'id' => $car->id,
                    'car_number' => $car->car_number,
                    'total_cost' => $totalCost,
                    'total_paid' => $totalPaid,
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
        $bookings = \App\Models\Booking::query()
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
        $bookings = \App\Models\Booking::query()
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
        $bookings = \App\Models\Booking::query()
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
