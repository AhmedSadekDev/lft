<?php

namespace App\Http\Controllers\Admin;

use App\Exports\EInvoice\AccountCertificateExport;
use App\Exports\EInvoice\ComprehensiveReportExport;
use App\Exports\EInvoice\Form41Export;
use App\Exports\EInvoice\TransactionStatementExport;
use App\Exports\EInvoice\VatPurchasesReportExport;
use App\Exports\EInvoice\VatSalesReportExport;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Invoice;
use App\Services\EInvoiceExportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EInvoiceController extends Controller
{
    public function __construct(
        protected EInvoiceExportService $exportService
    ) {
        $this->middleware(function ($request, $next) {
            abort_unless(has_app_permission('e_invoices.index'), 403);

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = Invoice::query()->with([
            'booking.company',
            'booking.bookingServices.service',
            'invoicePayments',
        ]);
        $this->exportService->applyLocalFilters($query, $request->only([
            'company_id', 'date_from', 'date_to', 'search', 'currency',
        ]));

        $filteredCount = (clone $query)->count();
        $invoices = $query->orderByDesc('id')->paginate(25)->withQueryString();

        $companies = Company::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.e-invoices.index', compact(
            'invoices',
            'companies',
            'filteredCount'
        ));
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'format' => 'required|string|in:pdf,json_xml,excel,database,table',
            'excel_type' => 'nullable|string|in:comprehensive,transactions,vat_sales,vat_purchases,form41,account_certificate',
            'invoice_ids' => 'nullable|array',
            'invoice_ids.*' => 'integer',
            'invoice_numbers' => 'nullable|string',
            'use_all_filtered' => 'nullable|boolean',
            'filename' => 'nullable|array',
            'excel' => 'nullable|array',
            'filters' => 'nullable|array',
        ]);

        $filenameOptions = $this->exportService->normalizeBoolOptions($validated['filename'] ?? []);
        $excelOptions = $this->exportService->normalizeBoolOptions($validated['excel'] ?? []);
        $format = $validated['format'];

        $numbers = [];
        if (! empty($validated['invoice_numbers'])) {
            $numbers = preg_split('/[\s,;]+/', (string) $validated['invoice_numbers']) ?: [];
        }

        $filters = $validated['filters'] ?? $request->input('filters', []);
        if (! empty($excelOptions['ignore_rejected'])) {
            $filters['ignore_rejected'] = true;
        }

        $invoices = $this->exportService->resolveLocalInvoices(
            $validated['invoice_ids'] ?? [],
            $numbers,
            $filters,
            filter_var($validated['use_all_filtered'] ?? false, FILTER_VALIDATE_BOOLEAN)
        );

        if ($invoices->isEmpty()) {
            return back()->with('error', 'لا توجد فواتير مطابقة للتصدير');
        }

        if ($format === 'database') {
            $path = $this->exportService->exportLocalDatabaseCsv($invoices);

            return response()->download($path, 'dashboard-invoices-database.csv')->deleteFileAfterSend(true);
        }

        if ($format === 'pdf') {
            $result = $this->exportService->exportLocalPdfZip($invoices, $filenameOptions);
            if (empty($result['path'])) {
                return back()->with('error', implode(' | ', $result['errors'] ?: ['فشل تصدير PDF']));
            }

            return response()->download($result['path'], 'dashboard-invoices-pdf.zip')->deleteFileAfterSend(true);
        }

        if ($format === 'json_xml') {
            $result = $this->exportService->exportLocalJsonXmlZip($invoices);
            if (empty($result['path'])) {
                return back()->with('error', implode(' | ', $result['errors'] ?: ['فشل تصدير JSON/XML']));
            }

            return response()->download($result['path'], 'dashboard-invoices-json-xml.zip')->deleteFileAfterSend(true);
        }

        $mapped = $this->exportService->mapLocalInvoices($invoices, $excelOptions);
        $table = $this->exportService->buildLocalColumnarTable($mapped, $excelOptions);

        if ($format === 'table') {
            return view('admin.e-invoices.table-preview', [
                'headers' => $table['headers'],
                'rows' => $table['rows'],
                'errors' => [],
                'count' => $mapped->count(),
            ]);
        }

        $excelType = $validated['excel_type'] ?? 'comprehensive';
        $export = match ($excelType) {
            'transactions' => new TransactionStatementExport($table, $mapped, $excelOptions),
            'vat_sales' => new VatSalesReportExport($table, $mapped, $excelOptions),
            'vat_purchases' => new VatPurchasesReportExport($table, $mapped, $excelOptions),
            'form41' => new Form41Export($table, $mapped, $excelOptions),
            'account_certificate' => new AccountCertificateExport($table, $mapped, $excelOptions),
            default => new ComprehensiveReportExport($table, $mapped, $excelOptions),
        };

        return Excel::download(
            $export,
            'dashboard-invoices-'.$excelType.'-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function lookupUuid(Request $request)
    {
        // Accept invoice numbers from the dedicated tab (field may arrive as uuids from older UI)
        $numbers = $request->input('invoice_numbers', $request->input('uuids'));
        $request->merge([
            'invoice_numbers' => $numbers,
            'invoice_ids' => [],
            'use_all_filtered' => false,
        ]);

        $request->validate([
            'invoice_numbers' => 'required|string',
            'format' => 'required|string|in:pdf,json_xml,excel,database,table',
            'excel_type' => 'nullable|string|in:comprehensive,transactions,vat_sales,vat_purchases,form41,account_certificate',
            'filename' => 'nullable|array',
            'excel' => 'nullable|array',
        ]);

        return $this->export($request);
    }

    public function taxpayerSearch(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|max:255',
        ]);

        $q = trim($validated['q']);
        $companies = Company::query()
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('tax_no', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'tax_no', 'phone', 'address']);

        return response()->json([
            'status' => true,
            'data' => [
                'result' => $companies->map(fn (Company $c) => [
                    'name' => $c->name,
                    'taxId' => $c->tax_no,
                    'address' => trim(($c->phone ? 'هاتف: '.$c->phone.' — ' : '').($c->address ?? '')),
                    'id' => $c->id,
                ])->values()->all(),
            ],
        ]);
    }
}
