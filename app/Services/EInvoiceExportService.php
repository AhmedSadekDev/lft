<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CompanyFatoorah;
use App\Models\Invoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use ZipArchive;

class EInvoiceExportService
{
    public function __construct(protected EInvoiceService $eInvoiceService)
    {
    }

    /**
     * @param  array<int>  $invoiceIds
     * @param  array<string>  $invoiceNumbers
     * @param  array<string, mixed>  $filters
     */
    public function resolveLocalInvoices(array $invoiceIds = [], array $invoiceNumbers = [], array $filters = [], bool $allFiltered = false): Collection
    {
        $query = Invoice::query()->with([
            'booking.company',
            'booking.bookingContainers.container',
            'booking.bookingServices.service',
            'invoicePayments',
        ]);

        if ($allFiltered) {
            $this->applyLocalFilters($query, $filters);
        } elseif (! empty($invoiceIds)) {
            $query->whereIn('id', $invoiceIds);
        } elseif (! empty($invoiceNumbers)) {
            $normalized = collect($invoiceNumbers)
                ->flatMap(fn ($n) => preg_split('/[\s,;]+/', (string) $n) ?: [])
                ->map(fn ($n) => trim($n))
                ->filter()
                ->unique()
                ->values()
                ->all();
            $query->whereIn('invoice_number', $normalized);
        } else {
            return collect();
        }

        if (! empty($filters['ignore_rejected'])) {
            $query->whereHas('booking');
        }

        return $query->orderByDesc('id')->limit(500)->get();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Invoice>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyLocalFilters($query, array $filters): void
    {
        if (! empty($filters['company_id'])) {
            $query->whereHas('booking', fn ($q) => $q->where('company_id', $filters['company_id']));
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['currency'])) {
            // currency column may be missing on legacy DB — ignore silently if so
            try {
                $query->where('currency', $filters['currency']);
            } catch (\Throwable $e) {
                // no-op
            }
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('id', $search)
                    ->orWhereHas('booking', function ($bq) use ($search) {
                        $bq->where('booking_number', 'like', "%{$search}%");
                    });
            });
        }
    }

    /**
     * Normalize checkbox payload ("1"/"0"/true/false) to bool map.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, bool>
     */
    public function normalizeBoolOptions(array $options): array
    {
        $out = [];
        foreach ($options as $key => $value) {
            $out[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return $out;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function mapLocalInvoices(Collection $invoices, array $excelOptions = []): Collection
    {
        $excelOptions = $this->normalizeBoolOptions($excelOptions);
        $seller = $this->resolveSellerIdentity();

        $mapped = $invoices->map(function (Invoice $invoice) use ($excelOptions, $seller) {
            $booking = $invoice->booking;
            $company = $booking?->company;

            if (! empty($excelOptions['ignore_rejected']) && ! $booking) {
                return null;
            }

            $beforeTax = (float) ($invoice->invoice_total_before_tax ?? 0);
            $vat = (float) ($invoice->value_added_tax_amount ?? 0);
            $salesTax = (float) ($invoice->sales_tax_amount ?? 0);
            $discount = (float) ($invoice->discount_amount ?? 0);
            $total = (float) ($invoice->invoice_total_after_tax ?? ($beforeTax + $vat));
            $paid = (float) ($invoice->invoicePayments?->sum('value') ?? 0);

            if (! empty($excelOptions['credit_note_negative']) && $total < 0) {
                $total = -abs($total);
            }

            $docType = $this->documentTypeLabel($booking?->type_of_action);

            return [
                'invoice' => $invoice,
                'invoice_id' => $invoice->id,
                'internal_id' => $invoice->invoice_number,
                'booking_id' => $invoice->booking_id,
                'booking_number' => $booking?->booking_number,
                'company_name' => $company?->name,
                'company_tax' => $company?->tax_no,
                'buyer_name' => $company?->name,
                'buyer_tax' => $company?->tax_no,
                'seller_name' => $seller['name'],
                'seller_tax' => $seller['tax'],
                'document_type' => $docType,
                'document_version' => 'لوحة التحكم',
                'status' => $paid >= $total && $total > 0 ? 'مسدد' : ($paid > 0 ? 'جزئي' : 'غير مسدد'),
                'date_issued' => optional($invoice->created_at)->toDateTimeString(),
                'date_received' => optional($invoice->updated_at)->toDateString(),
                'electronic_id' => (string) $invoice->id,
                'signature' => $booking?->signature_company ?: '—',
                'external_link' => $invoice->id ? route('booking-invoices.show', $invoice->id) : '',
                'po_reference' => $booking?->booking_number ?: '',
                'so_reference' => (string) ($invoice->booking_id ?: ''),
                'tax_total' => round($vat + $salesTax, 2),
                'discount_total' => $discount,
                'net_amount' => $beforeTax,
                'total_amount' => $total,
                'paid_amount' => $paid,
                'remaining_amount' => round($total - $paid, 2),
                'vat_amount' => $vat,
                'sales_tax_amount' => $salesTax,
                'transportation_total' => (float) ($invoice->transportation_total_before_vat ?? 0),
                'taxed_services_total' => (float) ($invoice->taxed_services_total_before_vat ?? 0),
                'untaxed_services_total' => (float) ($invoice->untaxed_services_total_before_vat ?? 0),
                'lines' => $this->buildInvoiceLines($invoice),
            ];
        })->filter()->values();

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $excelOptions
     * @return array{headers: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public function buildLocalColumnarTable(Collection $mappedRows, array $excelOptions): array
    {
        $excelOptions = $this->normalizeBoolOptions($excelOptions);
        $columns = $this->selectedLocalColumnDefs($excelOptions);
        $headers = array_values(array_map(fn ($c) => $c['label'], $columns));
        $groupItems = ! empty($excelOptions['group_items']);

        if ($groupItems) {
            $headers[] = 'البند';
            $headers[] = 'قيمة البند';
        }

        $rows = [];
        foreach ($mappedRows as $mapped) {
            if ($groupItems && ! empty($mapped['lines'])) {
                foreach ($mapped['lines'] as $line) {
                    $rows[] = array_merge(
                        $this->rowValuesForMapped($columns, $mapped, $line),
                        [
                            $line['description'] ?? '',
                            $line['amount'] ?? 0,
                        ]
                    );
                }
            } else {
                $row = $this->rowValuesForMapped($columns, $mapped, null);
                if ($groupItems) {
                    $row[] = '—';
                    $row[] = $mapped['total_amount'] ?? 0;
                }
                $rows[] = $row;
            }
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * @param  array<int, array{key: string, label: string}>  $columns
     * @param  array<string, mixed>  $mapped
     * @param  array<string, mixed>|null  $line
     * @return array<int, mixed>
     */
    protected function rowValuesForMapped(array $columns, array $mapped, ?array $line): array
    {
        $values = [];
        foreach ($columns as $column) {
            $key = $column['key'];
            $values[] = match ($key) {
                'seller_data' => trim(($mapped['seller_name'] ?? '').' / '.($mapped['seller_tax'] ?? '')),
                'buyer_data' => trim(($mapped['buyer_name'] ?? '').' / '.($mapped['buyer_tax'] ?? '')),
                'tax_columns' => 'ض.ق.م: '.number_format((float) ($mapped['vat_amount'] ?? 0), 2)
                    .' | مبيعات: '.number_format((float) ($mapped['sales_tax_amount'] ?? 0), 2),
                'discount_columns' => $mapped['discount_total'] ?? 0,
                'total_amount' => $line['amount'] ?? ($mapped['total_amount'] ?? 0),
                default => $mapped[$key] ?? '',
            };
        }

        return $values;
    }

    /**
     * @return array<int, array{description: string, amount: float, type: string}>
     */
    protected function buildInvoiceLines(Invoice $invoice): array
    {
        $lines = [];
        $booking = $invoice->booking;
        if (! $booking) {
            return $lines;
        }

        $booking->loadMissing(['bookingContainers.container', 'bookingServices.service']);

        foreach ($booking->bookingContainers ?? [] as $container) {
            $lines[] = [
                'description' => 'نقل: '.($container->container?->full_name ?? ('حاوية #'.$container->id)),
                'amount' => (float) ($container->price ?? 0),
                'type' => 'transportation',
            ];
        }

        foreach ($booking->bookingServices ?? [] as $service) {
            $lines[] = [
                'description' => 'خدمة: '.($service->full_name ?? ($service->service?->name ?? ('#'.$service->id))),
                'amount' => (float) ($service->price ?? $service->total ?? 0),
                'type' => 'service',
            ];
        }

        if (empty($lines)) {
            $lines[] = [
                'description' => 'إجمالي الفاتورة',
                'amount' => (float) ($invoice->invoice_total_after_tax ?? 0),
                'type' => 'total',
            ];
        }

        return $lines;
    }

    /**
     * @return array{name: string, tax: string}
     */
    protected function resolveSellerIdentity(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $fatoorah = CompanyFatoorah::query()->orderBy('id')->first();
        $cached = [
            'name' => $fatoorah?->company_name
                ?: config('app.name', 'Leader for Trans'),
            'tax' => env('COMPANY_TAX_NO', '765717972'),
        ];

        return $cached;
    }

    protected function documentTypeLabel($typeOfAction): string
    {
        return match ((string) $typeOfAction) {
            '0' => 'صادر',
            '1' => 'وارد',
            '2' => 'تخليص',
            '3', '4' => 'عملية خاصة',
            default => 'فاتورة',
        };
    }

    /**
     * @param  array<string, mixed>  $filenameOptions
     */
    public function buildLocalPdfFilename(array $mapped, array $filenameOptions): string
    {
        $filenameOptions = $this->normalizeBoolOptions($filenameOptions);
        $parts = [];
        if (! empty($filenameOptions['internal_id'])) {
            $parts[] = $mapped['internal_id'] ?? ('INV-'.$mapped['invoice_id']);
        }
        if (! empty($filenameOptions['electronic_id'])) {
            $parts[] = $mapped['invoice_id'] ?? 'id';
        }
        if (! empty($filenameOptions['invoice_date']) && ! empty($mapped['date_issued'])) {
            $parts[] = substr((string) $mapped['date_issued'], 0, 10);
        }
        if (! empty($filenameOptions['document_type'])) {
            $parts[] = $mapped['document_type'] ?? 'I';
        }
        if (! empty($filenameOptions['seller_name'])) {
            $parts[] = $mapped['seller_name'] ?? 'seller';
        }
        if (! empty($filenameOptions['seller_tax'])) {
            $parts[] = $mapped['seller_tax'] ?: 'seller-tax';
        }
        if (! empty($filenameOptions['buyer_name'])) {
            $parts[] = $mapped['buyer_name'] ?? 'buyer';
        }
        if (! empty($filenameOptions['buyer_tax'])) {
            $parts[] = $mapped['buyer_tax'] ?: 'buyer-tax';
        }
        if (! empty($filenameOptions['po_reference'])) {
            $parts[] = $mapped['booking_number'] ?? 'booking';
        }
        if (! empty($filenameOptions['so_reference'])) {
            $parts[] = $mapped['booking_id'] ?? 'booking-id';
        }

        if (empty($parts)) {
            $parts[] = 'invoice-'.($mapped['invoice_id'] ?? Str::random(6));
        }

        return collect($parts)->map(fn ($p) => Str::slug((string) $p, '_'))->filter()->implode('__').'.pdf';
    }

    public const PDF_MAX_INVOICES = 40;

    /**
     * Build one multi-page PDF (safer than hundreds of separate mPDF runs).
     *
     * @param  array<string, mixed>  $filenameOptions
     * @return array{path: string, download_name: string, errors: array<int, string>}
     */
    public function exportLocalPdfZip(Collection $invoices, array $filenameOptions): array
    {
        $filenameOptions = $this->normalizeBoolOptions($filenameOptions);

        if ($invoices->count() > self::PDF_MAX_INVOICES) {
            return [
                'path' => '',
                'download_name' => '',
                'errors' => [
                    'تصدير PDF يدعم حتى '.self::PDF_MAX_INVOICES.' فاتورة فقط. الحالي: '.$invoices->count()
                    .' — قلّل التحديد أو استخدم Excel / قاعدة البيانات للكميات الكبيرة.',
                ],
            ];
        }

        $mapped = $this->mapLocalInvoices($invoices);
        if ($mapped->isEmpty()) {
            return ['path' => '', 'download_name' => '', 'errors' => ['لا توجد فواتير لتصدير PDF']];
        }

        $this->ensureTempDir();
        $tmp = storage_path('app/temp/local-invoices-'.Str::random(8).'.pdf');

        try {
            $html = view('admin.e-invoices.pdf-batch', [
                'rows' => $mapped,
            ])->render();

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'default_font' => 'dejavusans',
                'directionality' => 'rtl',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 10,
                'margin_bottom' => 10,
                'tempDir' => storage_path('app/temp'),
            ]);
            $mpdf->WriteHTML($html);
            $mpdf->Output($tmp, \Mpdf\Output\Destination::FILE);
        } catch (\Throwable $e) {
            @unlink($tmp);

            return [
                'path' => '',
                'download_name' => '',
                'errors' => ['فشل إنشاء PDF: '.$e->getMessage()],
            ];
        }

        $downloadName = $this->buildLocalPdfFilename($mapped->first(), $filenameOptions);
        if ($mapped->count() > 1) {
            $downloadName = 'invoices-'.$mapped->count().'-'.now()->format('Ymd-His').'.pdf';
        }

        return [
            'path' => $tmp,
            'download_name' => $downloadName,
            'errors' => [],
        ];
    }

    /**
     * @return array{path: string, errors: array<int, string>}
     */
    public function exportLocalJsonXmlZip(Collection $invoices): array
    {
        $mapped = $this->mapLocalInvoices($invoices);
        $tmp = $this->makeTempZipPath('local-invoices-json-xml');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return ['path' => '', 'errors' => ['تعذر إنشاء ملف ZIP']];
        }

        foreach ($mapped as $row) {
            /** @var Invoice $invoice */
            $invoice = $row['invoice'];
            $payload = [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'booking_id' => $invoice->booking_id,
                'booking_number' => $row['booking_number'],
                'company' => [
                    'name' => $row['company_name'],
                    'tax_no' => $row['company_tax'],
                ],
                'totals' => [
                    'before_tax' => $row['net_amount'],
                    'vat' => $row['vat_amount'],
                    'sales_tax' => $row['sales_tax_amount'],
                    'discount' => $row['discount_total'],
                    'total' => $row['total_amount'],
                    'paid' => $row['paid_amount'],
                    'remaining' => $row['remaining_amount'],
                ],
                'created_at' => $row['date_issued'],
            ];

            $folder = Str::slug((string) ($invoice->invoice_number ?: $invoice->id), '_');
            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';
            $zip->addFromString("{$folder}/invoice.json", $json);
            $zip->addFromString("{$folder}/invoice.xml", $this->arrayToXml($payload, 'invoice'));
        }

        $zip->close();

        return ['path' => $tmp, 'errors' => []];
    }

    public function exportLocalDatabaseCsv(Collection $invoices): string
    {
        $tmp = storage_path('app/temp/local-invoices-db-'.Str::random(8).'.csv');
        $this->ensureTempDir();
        $fh = fopen($tmp, 'w');
        fputcsv($fh, [
            'invoice_id', 'invoice_number', 'booking_id', 'booking_number',
            'company_name', 'company_tax', 'before_tax', 'vat', 'sales_tax',
            'discount', 'total', 'paid', 'remaining', 'status', 'created_at',
        ]);

        foreach ($this->mapLocalInvoices($invoices) as $row) {
            fputcsv($fh, [
                $row['invoice_id'],
                $row['internal_id'],
                $row['booking_id'],
                $row['booking_number'],
                $row['company_name'],
                $row['company_tax'],
                $row['net_amount'],
                $row['vat_amount'],
                $row['sales_tax_amount'],
                $row['discount_total'],
                $row['total_amount'],
                $row['paid_amount'],
                $row['remaining_amount'],
                $row['status'],
                $row['date_issued'],
            ]);
        }

        fclose($fh);

        return $tmp;
    }

    public function arrayToXml(array $data, string $root = 'root'): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><'.$root.'/>');
        $this->arrayToXmlRecursive($data, $xml);

        return $xml->asXML() ?: '';
    }

    protected function arrayToXmlRecursive(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            $key = is_numeric($key) ? 'item'.$key : preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string) $key);
            if (is_array($value)) {
                $child = $xml->addChild($key);
                $this->arrayToXmlRecursive($value, $child);
            } else {
                $xml->addChild($key, htmlspecialchars((string) $value));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $excelOptions
     * @return array<int, array{key: string, label: string}>
     */
    protected function selectedLocalColumnDefs(array $excelOptions): array
    {
        $excelOptions = $this->normalizeBoolOptions($excelOptions);

        // Always keep identity + money columns for usable reports.
        $selected = [
            ['key' => 'internal_id', 'label' => 'رقم الفاتورة'],
            ['key' => 'booking_number', 'label' => 'رقم الحجز'],
        ];

        $optional = [
            'document_type' => 'نوع المستند',
            'document_version' => 'إصدار المستند',
            'status' => 'الحالة',
            'date_issued' => 'تاريخ الإصدار',
            'date_received' => 'تاريخ الاستلام',
            'seller_data' => 'بيانات البائع',
            'buyer_data' => 'بيانات المشتري',
            'electronic_id' => 'معرف الفاتورة',
            'signature' => 'التوقيع / جهة التوقيع',
            'external_link' => 'رابط الفاتورة',
            'po_reference' => 'رقم الحجز (مرجع)',
            'so_reference' => 'معرف الحجز',
            'tax_columns' => 'أعمدة الضريبة',
            'discount_columns' => 'أعمدة الخصم',
        ];

        foreach ($optional as $key => $label) {
            if (! empty($excelOptions[$key])) {
                $selected[] = ['key' => $key, 'label' => $label];
            }
        }

        $selected[] = ['key' => 'net_amount', 'label' => 'قبل الضريبة'];
        $selected[] = ['key' => 'total_amount', 'label' => 'الإجمالي'];
        $selected[] = ['key' => 'paid_amount', 'label' => 'المدفوع'];
        $selected[] = ['key' => 'remaining_amount', 'label' => 'المتبقي'];

        return $selected;
    }

    protected function makeTempZipPath(string $prefix): string
    {
        $this->ensureTempDir();

        return storage_path('app/temp/'.$prefix.'-'.Str::random(8).'.zip');
    }

    protected function ensureTempDir(): void
    {
        $dir = storage_path('app/temp');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    // ---- Legacy ETA helpers kept for UUID/taxpayer tabs ----

    /**
     * @param  array<int|string>  $bookingIds
     * @param  array<string>  $uuids
     * @param  array<string, mixed>  $options
     */
    public function resolveBookings(array $bookingIds = [], array $uuids = [], array $options = []): Collection
    {
        $query = Booking::query()
            ->with(['company', 'invoice'])
            ->whereNotNull('invoice_uuid')
            ->where('invoice_uuid', '!=', '');

        if (! empty($bookingIds)) {
            $query->whereIn('id', $bookingIds);
        } elseif (! empty($uuids)) {
            $normalized = collect($uuids)
                ->flatMap(fn ($u) => preg_split('/[\s,;]+/', (string) $u) ?: [])
                ->map(fn ($u) => trim($u))
                ->filter()
                ->unique()
                ->values()
                ->all();
            $query->whereIn('invoice_uuid', $normalized);
        }

        if (! empty($options['ignore_rejected'])) {
            $query->where(function ($q) {
                $q->whereNull('invoice_status')
                    ->orWhereNotIn('invoice_status', ['Invalid', 'Cancelled', 'Canceled', 'Rejected', 'Failed']);
            });
        }

        return $query->orderByDesc('id')->get();
    }
}
