<?php

namespace App\Http\Controllers\Admin;

use App\Exports\EInvoice\AccountCertificateExport;
use App\Exports\EInvoice\ComprehensiveReportExport;
use App\Exports\EInvoice\Form41Export;
use App\Exports\EInvoice\TransactionStatementExport;
use App\Exports\EInvoice\VatPurchasesReportExport;
use App\Exports\EInvoice\VatSalesReportExport;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Company;
use App\Models\CompanyFatoorah;
use App\Services\EInvoiceExportService;
use App\Services\EInvoiceService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EInvoiceController extends Controller
{
    public function __construct(
        protected EInvoiceService $eInvoiceService,
        protected EInvoiceExportService $exportService
    ) {
        // Use safe check: Spatie middleware throws if the permission cache is stale
        // even when the DB row exists.
        $this->middleware(function ($request, $next) {
            abort_unless(has_app_permission('e_invoices.index'), 403);

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = Booking::query()
            ->with(['company', 'invoice'])
            ->whereNotNull('invoice_uuid')
            ->where('invoice_uuid', '!=', '');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('invoice_status')) {
            $query->where('invoice_status', $request->invoice_status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('invoice_uuid', 'like', "%{$search}%")
                    ->orWhere('booking_number', 'like', "%{$search}%")
                    ->orWhere('submission_id', 'like', "%{$search}%");
            });
        }

        $filteredCount = (clone $query)->count();

        $bookings = $query->orderByDesc('id')->paginate(25)->withQueryString();

        $companies = Company::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $statuses = Booking::query()
            ->whereNotNull('invoice_uuid')
            ->where('invoice_uuid', '!=', '')
            ->whereNotNull('invoice_status')
            ->distinct()
            ->pluck('invoice_status')
            ->filter()
            ->values();

        return view('admin.e-invoices.index', compact(
            'bookings',
            'companies',
            'statuses',
            'filteredCount'
        ));
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'format' => 'required|string|in:pdf,json_xml,excel,database,table',
            'excel_type' => 'nullable|string|in:comprehensive,transactions,vat_sales,vat_purchases,form41,account_certificate',
            'booking_ids' => 'nullable|array',
            'booking_ids.*' => 'integer',
            'uuids' => 'nullable|string',
            'use_all_filtered' => 'nullable|boolean',
            'filename' => 'nullable|array',
            'excel' => 'nullable|array',
            'filters' => 'nullable|array',
        ]);

        $filenameOptions = $validated['filename'] ?? [];
        $excelOptions = $validated['excel'] ?? [];
        $format = $validated['format'];

        $bookings = $this->resolveExportBookings($request, $validated, $excelOptions);

        if ($bookings->isEmpty()) {
            return back()->with('error', 'لا توجد فواتير مطابقة للتصدير');
        }

        if ($format === 'database') {
            $path = $this->exportService->exportDatabaseCsv($bookings);

            return response()->download($path, 'e-invoices-database.csv')->deleteFileAfterSend(true);
        }

        if ($format === 'pdf') {
            $result = $this->exportService->exportPdfZip($bookings, $filenameOptions);
            if (empty($result['path'])) {
                return back()->with('error', implode(' | ', $result['errors'] ?: ['فشل تصدير PDF']));
            }

            return response()->download($result['path'], 'e-invoices-pdf.zip')->deleteFileAfterSend(true);
        }

        if ($format === 'json_xml') {
            $result = $this->exportService->exportJsonXmlZip($bookings);
            if (empty($result['path'])) {
                return back()->with('error', implode(' | ', $result['errors'] ?: ['فشل تصدير JSON/XML']));
            }

            return response()->download($result['path'], 'e-invoices-json-xml.zip')->deleteFileAfterSend(true);
        }

        $hydrated = $this->exportService->hydrateDocuments($bookings, true, false);
        $mapped = $this->exportService->mapRowsForColumns($hydrated['rows'], $excelOptions);
        $table = $this->exportService->buildColumnarTable($mapped, $excelOptions);

        if ($format === 'table') {
            return view('admin.e-invoices.table-preview', [
                'headers' => $table['headers'],
                'rows' => $table['rows'],
                'errors' => $hydrated['errors'],
                'count' => $mapped->count(),
            ]);
        }

        // excel
        $excelType = $validated['excel_type'] ?? 'comprehensive';
        $export = match ($excelType) {
            'transactions' => new TransactionStatementExport($table, $mapped, $excelOptions),
            'vat_sales' => new VatSalesReportExport($table, $mapped, $excelOptions),
            'vat_purchases' => new VatPurchasesReportExport($table, $mapped, $excelOptions),
            'form41' => new Form41Export($table, $mapped, $excelOptions),
            'account_certificate' => new AccountCertificateExport($table, $mapped, $excelOptions),
            default => new ComprehensiveReportExport($table, $mapped, $excelOptions),
        };

        $fileName = 'e-invoices-'.$excelType.'-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download($export, $fileName);
    }

    public function lookupUuid(Request $request)
    {
        $validated = $request->validate([
            'uuids' => 'required|string',
            'format' => 'required|string|in:pdf,json_xml,excel,database,table',
            'excel_type' => 'nullable|string|in:comprehensive,transactions,vat_sales,vat_purchases,form41,account_certificate',
            'filename' => 'nullable|array',
            'excel' => 'nullable|array',
        ]);

        $request->merge([
            'uuids' => $validated['uuids'],
            'booking_ids' => [],
            'use_all_filtered' => false,
        ]);

        return $this->export($request);
    }

    public function taxpayerSearch(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|max:255',
            'company_fatoorah_id' => 'nullable|integer',
        ]);

        $accessToken = null;
        if (! empty($validated['company_fatoorah_id'])) {
            $company = CompanyFatoorah::find($validated['company_fatoorah_id']);
            if ($company) {
                $accessToken = $this->eInvoiceService->getAccessToken(
                    $company->ETA_CLIENT_ID,
                    $company->ETA_CLIENT_SECRET
                );
            }
        }

        if (! $accessToken) {
            $first = CompanyFatoorah::query()->orderBy('id')->first();
            if ($first) {
                $accessToken = $this->eInvoiceService->getAccessToken(
                    $first->ETA_CLIENT_ID,
                    $first->ETA_CLIENT_SECRET
                );
            } else {
                $accessToken = $this->eInvoiceService->getAccessToken();
            }
        }

        if (! $accessToken) {
            return response()->json([
                'status' => false,
                'message' => 'تعذر الحصول على توكن ETA',
            ], 422);
        }

        $result = $this->eInvoiceService->searchTaxpayer($validated['q'], $accessToken);

        return response()->json($result, ($result['status'] ?? false) ? 200 : 422);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $excelOptions
     */
    protected function resolveExportBookings(Request $request, array $validated, array $excelOptions)
    {
        $options = [
            'ignore_rejected' => ! empty($excelOptions['ignore_rejected']),
        ];

        $uuids = [];
        if (! empty($validated['uuids'])) {
            $uuids = preg_split('/[\s,;]+/', (string) $validated['uuids']) ?: [];
        }

        if (! empty($validated['use_all_filtered'])) {
            $filters = $validated['filters'] ?? $request->input('filters', []);
            $query = Booking::query()
                ->with(['company', 'invoice'])
                ->whereNotNull('invoice_uuid')
                ->where('invoice_uuid', '!=', '');

            if (! empty($filters['company_id'])) {
                $query->where('company_id', $filters['company_id']);
            }
            if (! empty($filters['invoice_status'])) {
                $query->where('invoice_status', $filters['invoice_status']);
            }
            if (! empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }
            if (! empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }
            if (! empty($filters['search'])) {
                $search = trim((string) $filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_uuid', 'like', "%{$search}%")
                        ->orWhere('booking_number', 'like', "%{$search}%")
                        ->orWhere('submission_id', 'like', "%{$search}%");
                });
            }

            if (! empty($options['ignore_rejected'])) {
                $query->where(function ($q) {
                    $q->whereNull('invoice_status')
                        ->orWhereNotIn('invoice_status', ['Invalid', 'Cancelled', 'Canceled', 'Rejected', 'Failed']);
                });
            }

            return $query->orderByDesc('id')->limit(500)->get();
        }

        return $this->exportService->resolveBookings(
            $validated['booking_ids'] ?? [],
            $uuids,
            $options
        );
    }
}
