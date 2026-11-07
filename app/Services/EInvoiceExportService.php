<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CompanyFatoorah;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ZipArchive;

class EInvoiceExportService
{
    public function __construct(protected EInvoiceService $eInvoiceService)
    {
    }

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

    /**
     * Enrich bookings with ETA document details/raw when credentials are available.
     *
     * @return array{rows: Collection, errors: array<int, string>}
     */
    public function hydrateDocuments(Collection $bookings, bool $needDetails = true, bool $needRaw = false): array
    {
        $errors = [];
        $tokenCache = [];

        $rows = $bookings->map(function (Booking $booking) use (&$errors, &$tokenCache, $needDetails, $needRaw) {
            $row = [
                'booking' => $booking,
                'uuid' => $booking->invoice_uuid,
                'status' => $booking->invoice_status,
                'internal_id' => $booking->invoice?->invoice_number
                    ? (string) $booking->invoice->invoice_number
                    : ('INV-'.($booking->booking_number ?? $booking->id)),
                'company_name' => $booking->company?->name,
                'company_tax' => $booking->company?->tax_no,
                'details' => null,
                'raw' => null,
                'pdf' => null,
            ];

            $token = $this->tokenForBooking($booking, $tokenCache);
            if (! $token) {
                $errors[] = "تعذر الحصول على توكن ETA للفاتورة {$booking->invoice_uuid}";

                return $row;
            }

            if ($needDetails) {
                $details = $this->eInvoiceService->getInvoiceDetails($booking->invoice_uuid, $token);
                if (($details['status'] ?? false) === true) {
                    $row['details'] = $details['data'] ?? null;
                } else {
                    $errors[] = "تعذر جلب تفاصيل {$booking->invoice_uuid}: ".($details['message'] ?? '');
                }
            }

            if ($needRaw) {
                $raw = $this->eInvoiceService->getInvoice($booking->invoice_uuid, $token);
                if (is_array($raw) && ! isset($raw['status'])) {
                    $row['raw'] = $raw;
                } elseif (is_array($raw) && ($raw['status'] ?? true) === false) {
                    $errors[] = "تعذر جلب JSON للفاتورة {$booking->invoice_uuid}: ".($raw['message'] ?? '');
                } else {
                    $row['raw'] = $raw;
                }
            }

            return $row;
        });

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * @param  array<string, mixed>  $filenameOptions
     */
    public function buildPdfFilename(array $row, array $filenameOptions): string
    {
        $parts = [];
        $details = is_array($row['details'] ?? null) ? $row['details'] : [];
        $raw = is_array($row['raw'] ?? null) ? $row['raw'] : [];
        $doc = $raw['document'] ?? $raw;

        $issuer = $details['issuer'] ?? ($doc['issuer'] ?? []);
        $receiver = $details['receiver'] ?? ($doc['receiver'] ?? []);
        $dateIssued = $details['dateTimeIssued']
            ?? $details['dateTimeReceived']
            ?? ($doc['dateTimeIssued'] ?? null);

        if (! empty($filenameOptions['internal_id'])) {
            $parts[] = $row['internal_id'] ?? 'INV';
        }
        if (! empty($filenameOptions['electronic_id'])) {
            $parts[] = $row['uuid'] ?? 'uuid';
        }
        if (! empty($filenameOptions['invoice_date']) && $dateIssued) {
            $parts[] = substr((string) $dateIssued, 0, 10);
        }
        if (! empty($filenameOptions['document_type'])) {
            $parts[] = $details['documentType'] ?? ($doc['documentType'] ?? 'I');
        }
        if (! empty($filenameOptions['seller_name'])) {
            $parts[] = $issuer['name'] ?? ($issuer['id'] ?? 'seller');
        }
        if (! empty($filenameOptions['seller_tax'])) {
            $parts[] = $issuer['id'] ?? 'seller-tax';
        }
        if (! empty($filenameOptions['buyer_name'])) {
            $parts[] = $receiver['name'] ?? ($row['company_name'] ?? 'buyer');
        }
        if (! empty($filenameOptions['buyer_tax'])) {
            $parts[] = $receiver['id'] ?? ($row['company_tax'] ?? 'buyer-tax');
        }
        if (! empty($filenameOptions['po_reference'])) {
            $parts[] = $details['purchaseOrderReference'] ?? ($doc['purchaseOrderReference'] ?? 'po');
        }
        if (! empty($filenameOptions['so_reference'])) {
            $parts[] = $details['salesOrderReference'] ?? ($doc['salesOrderReference'] ?? 'so');
        }

        if (empty($parts)) {
            $parts[] = $row['uuid'] ?? ('invoice-'.Str::random(6));
        }

        $name = collect($parts)
            ->map(fn ($p) => Str::slug((string) $p, '_'))
            ->filter()
            ->implode('__');

        return $name.'.pdf';
    }

    /**
     * @param  array<string, mixed>  $filenameOptions
     */
    public function buildRelativePath(array $row, array $filenameOptions, string $filename): string
    {
        $details = is_array($row['details'] ?? null) ? $row['details'] : [];
        $raw = is_array($row['raw'] ?? null) ? $row['raw'] : [];
        $doc = $raw['document'] ?? $raw;
        $issuer = $details['issuer'] ?? ($doc['issuer'] ?? []);
        $receiver = $details['receiver'] ?? ($doc['receiver'] ?? []);

        $segments = [];
        if (! empty($filenameOptions['folder_per_seller'])) {
            $segments[] = Str::slug((string) ($issuer['name'] ?? $issuer['id'] ?? 'seller'), '_');
        }
        if (! empty($filenameOptions['folder_per_buyer'])) {
            $segments[] = Str::slug((string) ($receiver['name'] ?? $row['company_name'] ?? 'buyer'), '_');
        }

        $segments[] = $filename;

        return implode('/', $segments);
    }

    /**
     * @param  array<string, mixed>  $filenameOptions
     * @return array{path: string, errors: array<int, string>}
     */
    public function exportPdfZip(Collection $bookings, array $filenameOptions): array
    {
        $hydrated = $this->hydrateDocuments($bookings, true, false);
        $errors = $hydrated['errors'];
        $tokenCache = [];

        $tmp = $this->makeTempZipPath('eta-pdfs');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return ['path' => '', 'errors' => array_merge($errors, ['تعذر إنشاء ملف ZIP'])];
        }

        $added = 0;
        foreach ($hydrated['rows'] as $row) {
            /** @var Booking $booking */
            $booking = $row['booking'];
            $token = $this->tokenForBooking($booking, $tokenCache);
            if (! $token) {
                continue;
            }

            $pdf = $this->eInvoiceService->getInvoicePDF($booking->invoice_uuid, $token);
            if (! $pdf) {
                $errors[] = "تعذر تحميل PDF للفاتورة {$booking->invoice_uuid}";
                continue;
            }

            $filename = $this->buildPdfFilename($row, $filenameOptions);
            $path = $this->buildRelativePath($row, $filenameOptions, $filename);
            $zip->addFromString($path, $pdf);
            $added++;
        }

        $zip->close();

        if ($added === 0) {
            @unlink($tmp);

            return ['path' => '', 'errors' => array_merge($errors, ['لم يتم إضافة أي ملفات PDF'])];
        }

        return ['path' => $tmp, 'errors' => $errors];
    }

    /**
     * @return array{path: string, errors: array<int, string>}
     */
    public function exportJsonXmlZip(Collection $bookings): array
    {
        $hydrated = $this->hydrateDocuments($bookings, false, true);
        $errors = $hydrated['errors'];

        $tmp = $this->makeTempZipPath('eta-json-xml');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return ['path' => '', 'errors' => array_merge($errors, ['تعذر إنشاء ملف ZIP'])];
        }

        $added = 0;
        foreach ($hydrated['rows'] as $row) {
            $uuid = $row['uuid'] ?? 'unknown';
            $raw = $row['raw'];
            if (! $raw) {
                continue;
            }

            $json = json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $zip->addFromString("{$uuid}/raw.json", $json ?: '{}');
            $zip->addFromString("{$uuid}/document.xml", $this->arrayToXml($raw, 'document'));
            $added++;
        }

        $zip->close();

        if ($added === 0) {
            @unlink($tmp);

            return ['path' => '', 'errors' => array_merge($errors, ['لم يتم إضافة أي ملفات JSON/XML'])];
        }

        return ['path' => $tmp, 'errors' => $errors];
    }

    /**
     * Flatten rows for CSV / TABLE / Excel shared mapping.
     *
     * @param  array<string, mixed>  $excelOptions
     * @return Collection<int, array<string, mixed>>
     */
    public function mapRowsForColumns(Collection $hydratedRows, array $excelOptions): Collection
    {
        return $hydratedRows->map(function (array $row) use ($excelOptions) {
            $details = is_array($row['details'] ?? null) ? $row['details'] : [];
            $raw = is_array($row['raw'] ?? null) ? $row['raw'] : [];
            $doc = $raw['document'] ?? $raw;
            $issuer = $details['issuer'] ?? ($doc['issuer'] ?? []);
            $receiver = $details['receiver'] ?? ($doc['receiver'] ?? []);
            /** @var Booking $booking */
            $booking = $row['booking'];

            $documentType = $details['documentType'] ?? ($doc['documentType'] ?? 'I');
            $total = (float) ($details['totalAmount'] ?? $doc['totalAmount'] ?? $booking->invoice?->invoice_total_before_tax ?? 0);
            if (! empty($excelOptions['credit_note_negative']) && in_array(strtoupper((string) $documentType), ['C', 'CN', 'CREDITNOTE'], true)) {
                $total = -abs($total);
            }

            $mapped = [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'uuid' => $row['uuid'],
                'status' => $details['status'] ?? $row['status'],
                'internal_id' => $row['internal_id'],
                'submission_id' => $booking->submission_id,
                'company_name' => $row['company_name'],
                'company_tax' => $row['company_tax'],
                'document_type' => $documentType,
                'document_version' => $details['documentTypeVersion'] ?? ($doc['documentTypeVersion'] ?? ''),
                'date_issued' => $details['dateTimeIssued'] ?? ($doc['dateTimeIssued'] ?? ''),
                'date_received' => $details['dateTimeReceived'] ?? ($details['receptionDateTime'] ?? ''),
                'seller_name' => $issuer['name'] ?? '',
                'seller_tax' => $issuer['id'] ?? '',
                'buyer_name' => $receiver['name'] ?? ($row['company_name'] ?? ''),
                'buyer_tax' => $receiver['id'] ?? ($row['company_tax'] ?? ''),
                'electronic_id' => $row['uuid'],
                'signature' => ! empty($details['signatures'] ?? $doc['signatures'] ?? null) ? 'yes' : '',
                'external_link' => $details['publicUrl'] ?? ($details['shareUrl'] ?? ''),
                'po_reference' => $details['purchaseOrderReference'] ?? ($doc['purchaseOrderReference'] ?? ''),
                'so_reference' => $details['salesOrderReference'] ?? ($doc['salesOrderReference'] ?? ''),
                'tax_total' => collect($details['taxTotals'] ?? $doc['taxTotals'] ?? [])->sum('amount'),
                'discount_total' => $details['totalDiscountAmount'] ?? ($doc['totalDiscountAmount'] ?? 0),
                'total_amount' => $total,
                'net_amount' => $details['netAmount'] ?? ($doc['netAmount'] ?? 0),
                'lines' => $details['invoiceLines'] ?? ($doc['invoiceLines'] ?? []),
            ];

            return $mapped;
        });
    }

    /**
     * @param  array<string, mixed>  $excelOptions
     * @return array{headers: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public function buildColumnarTable(Collection $mappedRows, array $excelOptions): array
    {
        $columns = $this->selectedColumnDefs($excelOptions);
        $headers = array_values(array_map(fn ($c) => $c['label'], $columns));

        $groupItems = ! empty($excelOptions['group_items']);
        if ($groupItems) {
            $headers[] = 'البند';
        }

        $rows = [];
        foreach ($mappedRows as $mapped) {
            if ($groupItems && ! empty($mapped['lines']) && is_array($mapped['lines'])) {
                foreach ($mapped['lines'] as $line) {
                    $rows[] = $this->rowValues($columns, $mapped, $line, true);
                }
            } else {
                $rows[] = $this->rowValues($columns, $mapped, null, $groupItems);
            }
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    public function exportDatabaseCsv(Collection $bookings): string
    {
        $tmp = storage_path('app/temp/eta-db-'.Str::random(8).'.csv');
        $this->ensureTempDir();
        $fh = fopen($tmp, 'w');
        fputcsv($fh, [
            'booking_id', 'booking_number', 'company_id', 'company_name', 'company_tax',
            'invoice_uuid', 'submission_id', 'invoice_status', 'signature_company_id',
            'created_at',
        ]);

        foreach ($bookings as $booking) {
            fputcsv($fh, [
                $booking->id,
                $booking->booking_number,
                $booking->company_id,
                $booking->company?->name,
                $booking->company?->tax_no,
                $booking->invoice_uuid,
                $booking->submission_id,
                $booking->invoice_status,
                $booking->signature_company_id,
                optional($booking->created_at)->toDateTimeString(),
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
    protected function selectedColumnDefs(array $excelOptions): array
    {
        $all = [
            'document_type' => 'نوع المستند',
            'document_version' => 'إصدار المستند',
            'status' => 'الحالة',
            'date_issued' => 'تاريخ الإصدار',
            'date_received' => 'تاريخ الاستلام',
            'seller_data' => 'بيانات البائع',
            'buyer_data' => 'بيانات المشتري',
            'electronic_id' => 'المعرف الإلكتروني',
            'signature' => 'التوقيع الإلكتروني',
            'external_link' => 'الرابط الخارجي',
            'po_reference' => 'مرجع أمر الشراء',
            'so_reference' => 'مرجع أمر البيع',
            'tax_columns' => 'أعمدة الضريبة',
            'discount_columns' => 'أعمدة الخصم',
            'total_amount' => 'الإجمالي',
            'internal_id' => 'المعرف الداخلي',
            'booking_number' => 'رقم الحجز',
        ];

        $selected = [];
        foreach ($all as $key => $label) {
            // Always include core identity columns; optional ones follow checkboxes.
            $always = in_array($key, ['total_amount', 'internal_id', 'booking_number'], true);
            if ($always || ! empty($excelOptions[$key])) {
                $selected[] = ['key' => $key, 'label' => $label];
            }
        }

        if (empty($selected)) {
            $selected[] = ['key' => 'electronic_id', 'label' => 'المعرف الإلكتروني'];
            $selected[] = ['key' => 'status', 'label' => 'الحالة'];
            $selected[] = ['key' => 'total_amount', 'label' => 'الإجمالي'];
        }

        return $selected;
    }

    /**
     * @param  array<int, array{key: string, label: string}>  $columns
     * @param  array<string, mixed>  $mapped
     * @param  array<string, mixed>|null  $line
     * @return array<int, mixed>
     */
    protected function rowValues(array $columns, array $mapped, ?array $line, bool $includeLineColumn = false): array
    {
        $values = [];
        foreach ($columns as $column) {
            $key = $column['key'];
            $values[] = match ($key) {
                'seller_data' => trim(($mapped['seller_name'] ?? '').' / '.($mapped['seller_tax'] ?? '')),
                'buyer_data' => trim(($mapped['buyer_name'] ?? '').' / '.($mapped['buyer_tax'] ?? '')),
                'tax_columns' => $line['taxableItems'][0]['amount'] ?? ($mapped['tax_total'] ?? ''),
                'discount_columns' => $line['discount']['amount'] ?? ($mapped['discount_total'] ?? ''),
                'total_amount' => $line['total'] ?? ($mapped['total_amount'] ?? ''),
                default => $mapped[$key] ?? ($line[$key] ?? ''),
            };
        }

        if ($includeLineColumn) {
            $values[] = $line['description'] ?? ($line['itemCode'] ?? '');
        }

        return $values;
    }

    /**
     * @param  array<string, string|null>  $tokenCache
     */
    protected function tokenForBooking(Booking $booking, array &$tokenCache): ?string
    {
        $companyId = $booking->signature_company_id;
        $cacheKey = $companyId ? 'cf-'.$companyId : 'env-default';

        if (array_key_exists($cacheKey, $tokenCache)) {
            return $tokenCache[$cacheKey];
        }

        if ($companyId) {
            $company = CompanyFatoorah::find($companyId);
            if ($company) {
                $tokenCache[$cacheKey] = $this->eInvoiceService->getAccessToken(
                    $company->ETA_CLIENT_ID,
                    $company->ETA_CLIENT_SECRET
                );

                return $tokenCache[$cacheKey];
            }
        }

        $tokenCache[$cacheKey] = $this->eInvoiceService->getAccessToken();

        return $tokenCache[$cacheKey];
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
}
