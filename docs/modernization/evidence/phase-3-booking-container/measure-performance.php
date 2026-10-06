<?php
require __DIR__.'/phase3-bootstrap.php';
require_once __DIR__.'/compare-legacy-vs-sql.php';

// Measure Legacy Performance
$queriesLegacy = [];
$db->listen(function ($q) use (&$queriesLegacy) {
    $queriesLegacy[] = ['sql' => $q->sql, 'time' => $q->time];
});

memory_reset_peak_usage();
$memBeforeLegacy = memory_get_usage();
$t0 = hrtime(true);

// Run 10 iterations of legacy for all stages, page 1, perPage 10
for ($i = 0; $i < 10; $i++) {
    $legacyOut = runLegacyLogic(null, 1, 10);
}
$tLegacy = (hrtime(true) - $t0) / 1e6 / 10;
$memLegacy = memory_get_peak_usage() - $memBeforeLegacy;
$queriesLegacyCount = count($queriesLegacy) / 10;

// Measure Candidate SQL Performance
$queriesSql = [];
$db->listen(function ($q) use (&$queriesSql) {
    $queriesSql[] = ['sql' => $q->sql, 'time' => $q->time];
});

memory_reset_peak_usage();
$memBeforeSql = memory_get_usage();
$t1 = hrtime(true);

// Run 10 iterations of candidate SQL for all stages, page 1, perPage 10
for ($i = 0; $i < 10; $i++) {
    $sqlOut = runCandidateSqlLogic(null, 1, 10);
}
$tSql = (hrtime(true) - $t1) / 1e6 / 10;
$memSql = memory_get_peak_usage() - $memBeforeSql;
$queriesSqlCount = count($queriesSql) / 10;

$performanceComparison = [
    'legacy' => [
        'queries_per_call' => $queriesLegacyCount,
        'avg_ms' => round($tLegacy, 2),
        'peak_memory_bytes' => $memLegacy,
        'total_bookings' => $legacyOut['total'],
        'page_bookings' => count($legacyOut['bookings']),
    ],
    'sql_first' => [
        'queries_per_call' => $queriesSqlCount,
        'avg_ms' => round($tSql, 2),
        'peak_memory_bytes' => $memSql,
        'total_bookings' => $sqlOut['total'],
        'page_bookings' => count($sqlOut['bookings']),
    ],
    'improvement' => [
        'queries_reduced' => $queriesLegacyCount - $queriesSqlCount,
        'query_reduction_pct' => round((($queriesLegacyCount - $queriesSqlCount) / $queriesLegacyCount) * 100, 1) . '%',
        'latency_speedup_ms' => round($tLegacy - $tSql, 2),
    ]
];

echo json_encode($performanceComparison, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
