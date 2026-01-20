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

        // حساب إجمالي الفواتير في الفترة
        $totalInvoices = $this->calculateTotalInvoices($invoices);

        // حساب إجمالي السداد في الفترة
        $totalPayments = $this->calculateTotalPayments($company, $fromDate, $toDate);

        // حساب الرصيد النهائي المستحق
        $finalBalance = $carriedForwardBalance + $totalInvoices - $totalPayments;

        // جلب تفاصيل السداد
        $payments = $this->getPaymentsInPeriod($company, $fromDate, $toDate);

        // إنشاء قائمة موحدة بجميع الحركات (فواتير + سداد) مرتبة حسب التاريخ
        $transactions = $this->buildTransactionsList($invoices, $payments, $carriedForwardBalance, $fromDate);

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
    private function buildTransactionsList($invoices, $payments, $carriedForwardBalance, $fromDate = null)
    {
        $transactions = collect();

        // إضافة الرصيد المرحّل كأول حركة
        if ($carriedForwardBalance != 0) {
            $startDate = $fromDate ? Carbon::parse($fromDate) : Carbon::now()->startOfYear();
            $transactions->push([
                'date' => $startDate->copy()->subDay(),
                'type' => 'carried_forward',
                'type_label' => 'رصيد مرحّل',
                'booking_number' => '',
                'invoice_number' => '',
                'previous_debit' => $carriedForwardBalance > 0 ? abs($carriedForwardBalance) : 0,
                'previous_credit' => $carriedForwardBalance < 0 ? abs($carriedForwardBalance) : 0,
                'discount' => 0,
                'tax' => 0,
                'attachment_statement' => '',
                'transportation' => 0,
                'total' => 0,
                'paid' => 0,
                'notes' => 'رصيد مرحّل من الفترة السابقة',
                'current_debit' => $carriedForwardBalance > 0 ? abs($carriedForwardBalance) : 0,
                'current_credit' => $carriedForwardBalance < 0 ? abs($carriedForwardBalance) : 0,
                'running_balance' => $carriedForwardBalance,
            ]);
        }

        // إضافة الفواتير
        foreach ($invoices as $invoice) {
            $invoiceObj = \App\Models\Invoice::find($invoice['id']);
            if (!$invoiceObj) continue;

            $transportationTotal = $invoiceObj->transportation_total_before_vat ?? 0;
            $vatAmount = $invoiceObj->value_added_tax_amount ?? 0;
            $discountAmount = $invoiceObj->discount_amount ?? 0;
            $totalInvoice = $invoice['total'];
            $paidAmount = $invoice['paid'];

            $currentBalance = ($transactions->last()['running_balance'] ?? $carriedForwardBalance) + $totalInvoice;

            $transactions->push([
                'date' => $invoice['date'],
                'type' => 'invoice',
                'type_label' => 'فاتورة نقل',
                'booking_number' => $invoice['booking_number'],
                'invoice_number' => $invoice['invoice_number'],
                'previous_debit' => 0,
                'previous_credit' => 0,
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

        // إضافة المدفوعات
        foreach ($payments as $payment) {
            $currentBalance = ($transactions->last()['running_balance'] ?? $carriedForwardBalance) - $payment->value;

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

            $transactions->push([
                'date' => $payment->created_at,
                'type' => 'payment',
                'type_label' => 'قام العميل بسداد',
                'booking_number' => '',
                'invoice_number' => $payment->invoice->invoice_number ?? '',
                'previous_debit' => 0,
                'previous_credit' => 0,
                'discount' => 0,
                'tax' => 0,
                'attachment_statement' => '',
                'transportation' => 0,
                'total' => 0,
                'paid' => $payment->value,
                'notes' => $notes,
                'current_debit' => 0,
                'current_credit' => $payment->value,
                'running_balance' => $currentBalance,
            ]);
        }

        // ترتيب حسب التاريخ
        return $transactions->sortBy('date')->values();
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
        $transactions = $this->buildTransactionsList($invoices, $payments, $carriedForwardBalance, $fromDate);

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
        $transactions = $this->buildTransactionsList($invoices, $payments, $carriedForwardBalance, $fromDate);

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
        $vault = Vault::first();

        // حساب الرصيد المستحق الحالي
        $currentBalance = $this->calculateCurrentBalance($company);

        return view('admin.accounts.payment', compact('company', 'vault', 'currentBalance'));
    }

    /**
     * معالجة السداد
     */
    public function processPayment(Request $request, $companyId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $company = Company::findOrFail($companyId);
        $vault = Vault::first();

        if (!$vault) {
            return redirect()->back()->with('error', 'الخزنة الأساسية غير موجودة');
        }

        // حساب الرصيد المستحق
        $currentBalance = $this->calculateCurrentBalance($company);

        if ($request->amount > $currentBalance) {
            return redirect()->back()->with('error', 'المبلغ المدخل أكبر من الرصيد المستحق');
        }

        if ($request->amount > $vault->amount) {
            return redirect()->back()->with('error', 'الخزنة لا تحتوي على رصيد كافي');
        }

        DB::beginTransaction();

        try {
            // خصم المبلغ من الخزنة
            $vault->amount -= $request->amount;
            $vault->save();

            // تسجيل معاملة الخزنة
            VaultTransaction::create([
                'bank_id' => null,
                'name' => "سداد من {$company->name}",
                'amount' => $request->amount,
                'type' => 0 // Debit (منصرف)
            ]);

            // توزيع المبلغ على الفواتير (من الأقدم إلى الأحدث)
            $remainingPayment = $request->amount;
            $invoices = $company->bookings()
                ->whereHas('invoice')
                ->with('invoice')
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($invoices as $booking) {
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
                    $paymentAmount = min($remainingAmount, $remainingPayment);

                    // تسجيل السداد
                    $paymentData = [
                        'invoice_id' => $invoice->id,
                        'company_id' => $company->id,
                        'value' => $paymentAmount,
                        'user_id' => auth()->id()
                    ];

                    // رفع الصورة إذا كانت موجودة
                    if ($request->hasFile('image')) {
                        $imageName = time() . '_payment.' . $request->image->extension();
                        $imagePath = $request->image->storeAs('invoice_payments', $imageName, 'public');
                        $paymentData['image'] = "storage/" . $imagePath;
                    } else {
                        $paymentData['image'] = '';
                    }

                    // حفظ تاريخ السداد والملاحظات في created_at
                    $payment = InvoicePayment::create($paymentData);

                    // تحديث التاريخ والملاحظات إذا كانت متوفرة
                    if ($request->payment_date) {
                        $payment->created_at = $request->payment_date;
                        $payment->save();
                    }

                    $remainingPayment -= $paymentAmount;
                }
            }

            DB::commit();

            return redirect()->route('accounts.statement', $companyId)
                ->with('success', 'تم تسجيل السداد بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ أثناء تسجيل السداد: ' . $e->getMessage());
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

        // حساب إجمالي السداد قبل تاريخ البداية
        $payments = InvoicePayment::whereHas('invoice.booking', function ($query) use ($company, $fromDate) {
            $query->where('company_id', $company->id)
                  ->whereDate('invoice_payments.created_at', '<', $fromDate);
        })->sum('value');

        return $totalInvoices - $totalPayments;
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
                    'paid' => $invoice->invoicePayments->sum('value'),
                    'remaining' => $this->calculateInvoiceTotal($invoice) - $invoice->invoicePayments->sum('value'),
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
     * حساب إجمالي السداد في الفترة
     */
    private function calculateTotalPayments($company, $fromDate, $toDate)
    {
        return InvoicePayment::whereHas('invoice.booking', function ($query) use ($company, $fromDate, $toDate) {
            $query->where('company_id', $company->id)
                  ->whereBetween('invoice_payments.created_at', [$fromDate, $toDate . ' 23:59:59']);
        })->sum('value');
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
        ->with('invoice.booking')
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
                $totalPayments += $invoice->invoicePayments()->sum('value');
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
            ->with(['money_transfer', 'booking_containers', 'car', 'driver'])
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
                    'departure' => $container->departure->city ?? '',
                    'destination' => $container->loading->city ?? '',
                    'aging' => $container->aging->city ?? '',
                    'value' => $container->price ?? 0,
                    'custody' => $custodyAmount / ($containers->count() > 0 ? $containers->count() : 1),
                    'total1' => $container->price ?? 0,
                    'total2' => $custodyAmount / ($containers->count() > 0 ? $containers->count() : 1),
                    'debit_credit' => 'مدين',
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
        ->with(['bookingContainer', 'service', 'delivery_policy'])
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
                'container_no' => $container->container_no ?? $container->sail_of_number ?? '',
                'departure' => $container->departure->city ?? '',
                'destination' => $container->loading->city ?? '',
                'aging' => $container->aging->city ?? '',
                'value' => 0,
                'custody' => 0,
                'total1' => 0,
                'total2' => $expense->value,
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
