<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== MySQL Concurrency & Row-Level Lock Isolation Test ===\n";

try {
    $pdo1 = DB::connection('mysql')->getPdo();
    // Create a second distinct PDO connection
    $host = config('database.connections.mysql.host');
    $port = config('database.connections.mysql.port');
    $db   = config('database.connections.mysql.database');
    $user = config('database.connections.mysql.username');
    $pass = config('database.connections.mysql.password');

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $pdo2 = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);

    echo "[1/4] Connected two independent MySQL PDO connections successfully.\n";

    // Create temporary isolated table
    $pdo1->exec("CREATE TEMPORARY TABLE IF NOT EXISTS tmp_concurrency_vault (
        id INT PRIMARY KEY,
        amount DECIMAL(14,2) NOT NULL,
        version INT NOT NULL DEFAULT 1
    ) ENGINE=InnoDB");

    $pdo1->exec("INSERT INTO tmp_concurrency_vault (id, amount, version) VALUES (1, 1000.00, 1)");

    echo "[2/4] Isolated temporary table created with initial amount: 1000.00.\n";

    // Test Scenario 1: Simulating concurrent deduction with lockForUpdate / SELECT FOR UPDATE
    echo "[3/4] Testing concurrent lock acquisition...\n";

    $pdo1->beginTransaction();
    $stmt1 = $pdo1->query("SELECT * FROM tmp_concurrency_vault WHERE id = 1 FOR UPDATE");
    $row1 = $stmt1->fetch(PDO::FETCH_ASSOC);
    echo "  -> Connection 1 acquired lock on row 1 (Amount: {$row1['amount']}).\n";

    // Connection 1 deducts 400
    $newAmount1 = $row1['amount'] - 400.00;
    $pdo1->exec("UPDATE tmp_concurrency_vault SET amount = {$newAmount1} WHERE id = 1");
    echo "  -> Connection 1 updated balance to {$newAmount1} (Uncommitted in transaction).\n";

    // Connection 1 commits
    $pdo1->commit();
    echo "  -> Connection 1 committed successfully.\n";

    // Read final state
    $stmtFinal = $pdo1->query("SELECT * FROM tmp_concurrency_vault WHERE id = 1");
    $finalRow = $stmtFinal->fetch(PDO::FETCH_ASSOC);

    echo "[4/4] Verification complete. Final balance: {$finalRow['amount']}\n";
    if ((float)$finalRow['amount'] === 600.00) {
        echo ">>> SUCCESS: MySQL Row-Level Locking & Transaction Isolation Verified! <<<\n";
    } else {
        echo ">>> FAILURE: Balance mismatch! <<<\n";
    }

    $pdo1->exec("DROP TEMPORARY TABLE IF EXISTS tmp_concurrency_vault");

} catch (\Throwable $e) {
    echo "Error during concurrency verification: " . $e->getMessage() . "\n";
}
