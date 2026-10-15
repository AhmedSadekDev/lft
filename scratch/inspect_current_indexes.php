<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = [
    'bookings',
    'booking_containers',
    'booking_container_agents',
    'invoices',
    'receipts',
    'agent_expenses',
    'delivery_policies',
    'yards',
    'shipping_agents',
    'companies',
    'agents',
    'superagents',
];

$results = [];

foreach ($tables as $table) {
    try {
        $indexes = DB::connection('mysql')->select("
            SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, CARDINALITY, INDEX_TYPE
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
            ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX
        ", [$table]);

        $grouped = [];
        foreach ($indexes as $idx) {
            $grouped[$idx->INDEX_NAME][] = $idx->COLUMN_NAME;
        }

        $results[$table] = $grouped;
    } catch (\Throwable $e) {
        $results[$table] = 'ERROR: ' . $e->getMessage();
    }
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
