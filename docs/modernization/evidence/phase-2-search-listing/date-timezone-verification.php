<?php
require __DIR__.'/verification-bootstrap.php';
$zones = ['+00:00', '+03:00', '-05:00'];
$inputs = [
    [null, null], ['2026-09-01', null], [null, '2026-09-30'],
    ['2024-02-29', null], [null, '2024-02-29'],
    ['2026-04-24', null], [null, '2026-04-24'], ['2026-10-30', null], [null, '2026-10-30'],
    ['2026-09-01', '2026-09-30'], ['2026-09-30', '2026-09-01'],
    ['9999-12-31', null], [null, '9999-12-31'],
    ['2026-9-1', null], [null, '2026-9-1'],
    ['invalid', null], [null, 'invalid'], ['2024-02-30', null], [null, '2024-02-30'],
    ['2026-09-01 12:30:00', null], [null, '2026-09-01 12:30:00'], ['0', null], [null, '0'],
];
$pdo = $db->getPdo();
$originalZone = $pdo->query('SELECT @@session.time_zone')->fetchColumn();
$out = ['mode' => $mode, 'cases' => [], 'original_timezone' => $originalZone,
    'method' => 'Real booking rows; session timezone only, restored afterwards. No business writes or temporary tables.'];
try {
    foreach ($zones as $zone) {
        $pdo->exec("SET time_zone = '".$zone."'");
        foreach ($inputs as [$from, $to]) {
            $q = App\Models\Booking::query()->filterDateRange($from, $to)->orderBy('id')->toBase();
            try {
                $rows = $q->get();
                $result = ['outcome' => 'success', 'rows' => $rows->count(), 'sha256' => hash('sha256', json_encode($rows)),
                    'sql' => $q->toSql(), 'bindings' => $q->getBindings()];
            } catch (Throwable $e) {
                $result = ['outcome' => 'exception', 'class' => get_class($e), 'message' => $e->getMessage()];
            }
            $out['cases'][] = ['zone' => $zone, 'from' => $from, 'to' => $to, 'result' => $result];
        }
    }
} finally {
    $pdo->exec('SET time_zone = '.$pdo->quote($originalZone));
}
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
