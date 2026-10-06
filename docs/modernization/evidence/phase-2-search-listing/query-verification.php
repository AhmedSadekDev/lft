<?php
// Real-data query comparisons only. No Laravel boot, auth bypass, fixtures or DDL.
require dirname(__DIR__, 4).'/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__, 4))->safeLoad();
$p = new PDO('mysql:host='.$_ENV['DB_HOST'].';port='.($_ENV['DB_PORT'] ?? 3306).';dbname='.$_ENV['DB_DATABASE'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$p->exec('START TRANSACTION READ ONLY');
try {
    if ($p->query('SELECT DATABASE()')->fetchColumn() !== 'leader') throw new RuntimeException('Unexpected database');
    $conn = new Illuminate\Database\MySqlConnection($p, 'leader');
    Illuminate\Database\Eloquent\Model::setConnectionResolver(new Illuminate\Database\ConnectionResolver(['' => $conn]));
    $out = ['database' => 'leader', 'mode' => 'READ ONLY; ROLLBACK', 'cases' => [], 'indexes' => [], 'missing_tables' => []];
    foreach (['users', 'yards', 'vaults', 'vault_transactions'] as $table) {
        $s = $p->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $s->execute([$table]);
        if (!$s->fetchColumn()) $out['missing_tables'][] = $table;
    }
    function measure($q, $conn) {
        $times = [];
        $rows = null;
        for ($i = 0; $i < 11; $i++) {
            $start = hrtime(true);
            $rows = $q->get();
            if ($i) $times[] = (hrtime(true) - $start) / 1e6;
        }
        sort($times);
        return ['sql' => $q->toSql(), 'bindings' => $q->getBindings(),
            'rows' => $rows->count(), 'sha256' => hash('sha256', json_encode($rows)),
            'bytes' => strlen(json_encode($rows)), 'p50_ms' => $times[4], 'p95_ms' => $times[9],
            'explain' => $conn->select('EXPLAIN '.$q->toSql(), $q->getBindings())];
    }
    function compareCase(&$out, $name, $before, $after, $conn) {
        $a = measure($before, $conn);
        $b = measure($after, $conn);
        $a['total'] = (clone $before)->cloneWithout(['limit', 'offset', 'orders'])->cloneWithoutBindings(['order'])->count();
        $b['total'] = (clone $after)->cloneWithout(['limit', 'offset', 'orders'])->cloneWithoutBindings(['order'])->count();
        $equal = $a['sha256'] === $b['sha256'] && $a['rows'] === $b['rows'] && $a['total'] === $b['total'];
        $out['cases'][] = ['name' => $name, 'equal' => $equal, 'before' => $a, 'after' => $b];
    }
    $profiles = [
        [App\Models\Company::class, 'companies', ['name', 'email', 'phone', 'tax_no'], 20],
        [App\Models\PrivateCompany::class, 'private_companies', ['name', 'tax_no', 'commercial_register'], 20],
        [App\Models\Car::class, 'cars', ['car_number'], 15],
        [App\Models\Driver::class, 'drivers', ['name', 'phone'], 15],
    ];
    foreach ($profiles as [$model, $table, $fields, $perPage]) {
        $out['indexes'][$table] = $conn->select('SHOW INDEX FROM '.$table);
        $sample = $conn->table($table)->whereNotNull($fields[0])->value($fields[0]);
        $terms = array_unique(['0', 'شركة', '%', '_', "O'Reilly", 'phase2_no_match_9137', '', mb_substr((string)$sample, 0, 3)]);
        foreach ($terms as $term) {
            foreach ([1, 2, 999] as $page) {
                $before = $conn->table($table)->where(function ($q) use ($fields, $term) {
                    foreach ($fields as $i => $field) $q->{$i ? 'orWhere' : 'where'}($field, 'like', "%{$term}%");
                })->orderBy('id', 'desc')->forPage($page, $perPage);
                $after = (new $model)->newQueryWithoutScopes()->searchListing($term)->orderBy('id', 'desc')->forPage($page, $perPage)->toBase();
                compareCase($out, $table.'/search/'.hash('crc32b', $term).'/page/'.$page, $before, $after, $conn);
            }
        }
        if (in_array($table, ['companies', 'private_companies'])) {
            foreach (['id', 'name', 'tax_no', 'created_at'] as $column) {
                foreach (['asc', 'desc'] as $direction) {
                    compareCase($out, $table.'/sort/'.$column.'/'.$direction,
                        $conn->table($table)->orderBy($column, $direction)->limit(20),
                        (new $model)->newQueryWithoutScopes()->sortListing($column, $direction)->limit(20)->toBase(), $conn);
                }
            }
            foreach ([['unknown', 'asc'], ['name', 'invalid'], [[], []]] as $i => [$column, $direction]) {
                compareCase($out, $table.'/approved-fallback/'.$i,
                    $conn->table($table)->orderBy('id', 'desc')->limit(20),
                    (new $model)->newQueryWithoutScopes()->sortListing($column, $direction)->limit(20)->toBase(), $conn);
            }
        }
    }
    $out['indexes']['bookings'] = $conn->select('SHOW INDEX FROM bookings');
    foreach ([['2026-09-01', null], [null, '2026-09-30'], ['2024-02-29', null], [null, '2024-02-29'],
        ['2026-09-01', '2026-09-30'], [null, null], [null, '9999-12-31'], ['2026-09-30', '2026-09-01']] as [$from, $to]) {
        $before = $conn->table('bookings');
        if ($from && $to) $before->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);
        elseif ($from) $before->whereDate('created_at', '>=', $from);
        elseif ($to) $before->whereDate('created_at', '<=', $to);
        $after = (new App\Models\Booking)->newQueryWithoutScopes()->filterDateRange($from, $to);
        compareCase($out, 'bookings/dates/'.($from ?? 'null').'/'.($to ?? 'null'),
            $before->orderBy('id'), $after->orderBy('id')->toBase(), $conn);
    }
    $out['comparisons'] = count($out['cases']);
    $out['mismatches'] = count(array_filter($out['cases'], fn ($c) => !$c['equal']));
    $out['peak_process_memory_bytes'] = memory_get_peak_usage(true);
    echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($out['mismatches']) exit(1);
} finally {
    $p->exec('ROLLBACK');
}
