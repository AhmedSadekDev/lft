<?php
require __DIR__.'/verification-bootstrap.php';
$profiles = [
    ['companies', App\Http\Controllers\Admin\CompanyController::class, 'companies', 'name'],
    ['private-companies', App\Http\Controllers\Admin\PrivateCompanyController::class, 'private_companies', 'name'],
    ['cars', App\Http\Controllers\Admin\CarController::class, 'cars', 'car_number'],
    ['drivers', App\Http\Controllers\Admin\DriverController::class, 'drivers', 'name'],
];
function stableValue($value) {
    if ($value instanceof Illuminate\Pagination\LengthAwarePaginator) {
        return ['kind' => get_class($value), 'total' => $value->total(), 'per_page' => $value->perPage(),
            'current_page' => $value->currentPage(), 'last_page' => $value->lastPage(),
            'items' => stableValue($value->getCollection()), 'url1' => $value->url(1)];
    }
    if ($value instanceof Illuminate\Database\Eloquent\Model) {
        return ['class' => get_class($value), 'attributes' => $value->getAttributes(), 'relations' => stableValue($value->getRelations())];
    }
    if ($value instanceof Illuminate\Support\Collection) return $value->map(fn ($item) => stableValue($item))->all();
    if (is_array($value)) return array_map('stableValue', $value);
    return $value;
}
$cases = [];
foreach ($profiles as [$resource, $controller, $table, $field]) {
    $sample = $db->table($table)->whereNotNull($field)->value($field);
    $inputs = [[], ['search' => '0'], ['search' => 'شركة'], ['search' => '%'], ['search' => '_'],
        ['search' => "'"], ['search' => 'phase2_no_match_9137'], ['search' => ''],
        ['search' => '   '], ['search' => mb_substr((string)$sample, 0, 3)],
        ['page' => 2], ['page' => 999], ['search' => '%', 'page' => 2]];
    if (in_array($resource, ['companies', 'private-companies'])) {
        // Verify every legacy-column allowlist entry against the actual controller.
        $columns = $db->select('SHOW COLUMNS FROM '.$table);
        foreach ($columns as $column) {
            foreach (['asc', 'DESC'] as $direction) $inputs[] = ['sort_by' => $column->Field, 'sort_dir' => $direction];
        }
    }
    foreach ($inputs as $i => $input) $cases[] = [$resource.'/'.$i, $resource, $controller, $input, $input];
    if (in_array($resource, ['companies', 'private-companies'])) {
        foreach ([['sort_by' => 'unknown', 'sort_dir' => 'asc'], ['sort_by' => 'name', 'sort_dir' => 'invalid'],
            ['sort_by' => ['id'], 'sort_dir' => ['asc']]] as $i => $input) {
            $cases[] = [$resource.'/approved-fallback/'.$i, $resource, $controller, [], $input];
        }
    }
}
$dates = [[], ['date_from' => '2026-09-01'], ['date_to' => '2026-09-30'],
    ['date_from' => '2024-02-29'], ['date_to' => '2024-02-29'],
    ['date_from' => '2026-09-01', 'date_to' => '2026-09-30'],
    ['date_from' => '2026-09-30', 'date_to' => '2026-09-01'],
    ['date_to' => '9999-12-31'], ['date_to' => '2026-9-1'], ['date_to' => 'invalid'],
    ['date_from' => '2026-09-01', 'company' => 6, 'stage' => 'loading', 'page' => 2, 'per_page' => 15],
    ['date_to' => '2026-09-30', 'invoice_status' => '1', 'per_page' => 100]];
foreach ($dates as $i => $input) $cases[] = ['bookings/'.$i, 'bookings', App\Http\Controllers\Admin\BookingController::class, $input, $input];
$out = ['mode' => $mode, 'level' => 'QUERY VERIFIED — actual controller/view-data, no middleware or view rendering; not authenticated endpoint verification',
    'cases' => [], 'normalizations' => []];
foreach ($cases as [$name, $resource, $controller, $beforeInput, $afterInput]) {
    $input = $mode === 'baseline' ? $beforeInput : $afterInput;
    $request = Illuminate\Http\Request::create('http://localhost/dashboard/'.$resource, 'GET', $input);
    $app->instance('request', $request);
    Illuminate\Support\Facades\Facade::clearResolvedInstance('request');
    $db->flushQueryLog();
    $db->enableQueryLog();
    $start = hrtime(true);
    try {
        $view = $app->make($controller)->index($request);
        $data = stableValue($view->getData());
        $json = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
        $result = ['outcome' => 'success', 'view' => $view->name(), 'data_keys' => array_keys($data),
            'sha256' => hash('sha256', $json), 'bytes' => strlen($json)];
        foreach ($data as $key => $value) {
            if (is_array($value) && isset($value['kind'])) {
                $result['pagination'][$key] = array_diff_key($value, ['items' => true]);
                $result['ordered_ids'][$key] = array_map(fn ($item) => $item['attributes']['id'], $value['items']);
            }
        }
    } catch (Throwable $e) {
        $result = ['outcome' => 'exception', 'class' => get_class($e), 'message' => $e->getMessage()];
    }
    $result['latency_ms'] = (hrtime(true)-$start)/1e6;
    $result['query_count'] = count($db->getQueryLog());
    $db->disableQueryLog();
    $out['cases'][] = ['name' => $name, 'input' => $input, 'result' => $result];
}
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
