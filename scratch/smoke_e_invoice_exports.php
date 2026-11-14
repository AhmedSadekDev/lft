<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Exports\EInvoice\ComprehensiveReportExport;
use App\Exports\EInvoice\Form41Export;
use App\Exports\EInvoice\TransactionStatementExport;
use App\Exports\EInvoice\VatPurchasesReportExport;
use App\Exports\EInvoice\VatSalesReportExport;
use App\Exports\EInvoice\AccountCertificateExport;
use App\Models\Company;
use App\Services\EInvoiceExportService;
use Maatwebsite\Excel\Facades\Excel;

$svc = app(EInvoiceExportService::class);

$excelOpts = $svc->normalizeBoolOptions([
    'document_type' => '1',
    'status' => '1',
    'date_issued' => '1',
    'date_received' => '1',
    'seller_data' => '1',
    'buyer_data' => '1',
    'electronic_id' => '1',
    'tax_columns' => '1',
    'discount_columns' => '1',
    'group_items' => '1',
    'signature' => '0',
    'external_link' => '1',
    'po_reference' => '1',
    'so_reference' => '1',
]);

$filenameOpts = $svc->normalizeBoolOptions([
    'internal_id' => '1',
    'electronic_id' => '1',
    'invoice_date' => '1',
    'buyer_name' => '1',
    'folder_per_buyer' => '1',
]);

$invoices = $svc->resolveLocalInvoices([], [], [], false);
// take latest 2 by ids
$ids = \App\Models\Invoice::query()->orderByDesc('id')->limit(2)->pluck('id')->all();
$invoices = $svc->resolveLocalInvoices($ids);

echo 'sample_count='.$invoices->count().PHP_EOL;
abort_if($invoices->isEmpty(), 500, 'No invoices');

$mapped = $svc->mapLocalInvoices($invoices, $excelOpts);
echo 'mapped='.$mapped->count().PHP_EOL;
echo 'seller='.($mapped->first()['seller_name'] ?? '').PHP_EOL;
echo 'buyer='.($mapped->first()['buyer_name'] ?? '').PHP_EOL;
echo 'lines='.count($mapped->first()['lines'] ?? []).PHP_EOL;

$table = $svc->buildLocalColumnarTable($mapped, $excelOpts);
echo 'headers='.count($table['headers']).' rows='.count($table['rows']).PHP_EOL;
echo 'filename='.$svc->buildLocalPdfFilename($mapped->first(), $filenameOpts).PHP_EOL;

$csv = $svc->exportLocalDatabaseCsv($invoices);
echo 'csv='.(is_file($csv) ? filesize($csv) : 0).PHP_EOL;
@unlink($csv);

$json = $svc->exportLocalJsonXmlZip($invoices);
echo 'json_xml='.(is_file($json['path']) ? filesize($json['path']) : 0).PHP_EOL;
if (! empty($json['path'])) {
    @unlink($json['path']);
}

$pdf = $svc->exportLocalPdfZip($invoices, $filenameOpts);
echo 'pdf_ok='.(! empty($pdf['path']) && is_file($pdf['path']) ? '1' : '0').PHP_EOL;
echo 'pdf_errors='.count($pdf['errors']).PHP_EOL;
if (! empty($pdf['path'])) {
    @unlink($pdf['path']);
}

foreach ([
    'comprehensive' => new ComprehensiveReportExport($table, $mapped, $excelOpts),
    'transactions' => new TransactionStatementExport($table, $mapped, $excelOpts),
    'vat_sales' => new VatSalesReportExport($table, $mapped, $excelOpts),
    'vat_purchases' => new VatPurchasesReportExport($table, $mapped, $excelOpts),
    'form41' => new Form41Export($table, $mapped, $excelOpts),
    'account_certificate' => new AccountCertificateExport($table, $mapped, $excelOpts),
] as $name => $export) {
    $tmp = storage_path('app/temp/smoke-'.$name.'.xlsx');
    Excel::store($export, 'temp/smoke-'.$name.'.xlsx');
    $full = storage_path('app/temp/smoke-'.$name.'.xlsx');
    // maatwebsite store uses storage/app
    $full = storage_path('app/temp/smoke-'.$name.'.xlsx');
    if (! is_file($full)) {
        $full = storage_path('app/temp/smoke-'.$name.'.xlsx');
    }
    $path = storage_path('app/temp/smoke-'.$name.'.xlsx');
    // Excel::store path relative to disk local = storage/app
    $stored = storage_path('app/temp/smoke-'.$name.'.xlsx');
    echo $name.'='.(is_file(storage_path('app/temp/smoke-'.$name.'.xlsx')) ? 'ok' : 'missing').PHP_EOL;
    @unlink(storage_path('app/temp/smoke-'.$name.'.xlsx'));
}

$companies = Company::query()->where('name', 'like', '%هاي%')->limit(3)->get(['id', 'name', 'tax_no']);
echo 'company_search='.$companies->count().PHP_EOL;

echo 'ALL_OK'.PHP_EOL;
