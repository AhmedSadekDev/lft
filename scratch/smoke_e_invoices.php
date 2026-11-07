<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Booking;
use App\Services\EInvoiceExportService;
use Illuminate\Support\Collection;

$service = app(EInvoiceExportService::class);

// Empty collection CSV
$bookings = new Collection();
$path = $service->exportDatabaseCsv($bookings);
echo 'csv_ok='.(is_file($path) ? '1' : '0').PHP_EOL;
@unlink($path);

// Column mapping with fake row structure
$fakeBooking = new Booking();
$fakeBooking->forceFill([
    'id' => 1,
    'booking_number' => 'BK-TEST',
    'invoice_uuid' => 'uuid-test',
    'invoice_status' => 'Valid',
    'submission_id' => 'sub-1',
    'company_id' => 1,
]);
$fakeBooking->exists = false;

$hydrated = collect([[
    'booking' => $fakeBooking,
    'uuid' => 'uuid-test',
    'status' => 'Valid',
    'internal_id' => 'INV-1',
    'company_name' => 'Test Co',
    'company_tax' => '123',
    'details' => [
        'documentType' => 'I',
        'documentTypeVersion' => '1.0',
        'dateTimeIssued' => '2026-01-01T00:00:00Z',
        'issuer' => ['name' => 'Seller', 'id' => '111'],
        'receiver' => ['name' => 'Buyer', 'id' => '222'],
        'totalAmount' => 100,
        'netAmount' => 100,
        'taxTotals' => [['amount' => 0]],
        'invoiceLines' => [['description' => 'Line A', 'total' => 100]],
    ],
    'raw' => null,
]]);

$excelOptions = [
    'document_type' => true,
    'status' => true,
    'seller_data' => true,
    'buyer_data' => true,
    'electronic_id' => true,
    'tax_columns' => true,
    'group_items' => true,
];

$mapped = $service->mapRowsForColumns($hydrated, $excelOptions);
$table = $service->buildColumnarTable($mapped, $excelOptions);
echo 'headers='.count($table['headers']).PHP_EOL;
echo 'rows='.count($table['rows']).PHP_EOL;
echo 'filename='.$service->buildPdfFilename($hydrated->first(), [
    'internal_id' => true,
    'electronic_id' => true,
]).PHP_EOL;

echo 'routes='.implode(',', [
    route('e-invoices.index', [], false),
    route('e-invoices.export', [], false),
    route('e-invoices.lookup-uuid', [], false),
    route('e-invoices.taxpayer-search', [], false),
]).PHP_EOL;

echo 'OK'.PHP_EOL;
