<?php
require __DIR__.'/verification-bootstrap.php';
$seed = json_decode(file_get_contents(__DIR__.'/listing-source-inventory.json'), true);
$metadata = json_decode(file_get_contents(__DIR__.'/closure-metadata-before.json'), true);
$changed = ['App\\Http\\Controllers\\Admin\\CompanyController@index', 'App\\Http\\Controllers\\Admin\\PrivateCompanyController@index',
    'App\\Http\\Controllers\\Admin\\CarController@index', 'App\\Http\\Controllers\\Admin\\DriverController@index',
    'App\\Http\\Controllers\\Admin\\BookingController@index'];
$rows = [];
foreach ($seed['resources'] as $entry) {
    $source = file_get_contents($root.'/'.$entry['source']);
    preg_match('/namespace\s+([^;]+);/', $source, $ns);
    preg_match('/class\s+(\w+)/', $source, $class);
    $fqcn = $ns[1].'\\'.$class[1];
    $action = $fqcn.'@'.$entry['method'];
    $method = new ReflectionMethod($fqcn, $entry['method']);
    $lines = file($root.'/'.$entry['source']);
    $body = implode('', array_slice($lines, $method->getStartLine()-1, $method->getEndLine()-$method->getStartLine()+1));
    $routes = array_values(array_filter($metadata['routes'], fn ($r) => ltrim($r['action'], '\\') === $action));
    $models = [];
    preg_match_all('/use\s+(App\\\\Models\\\\\w+)(?:\s+as\s+(\w+))?\s*;/', $source, $imports, PREG_SET_ORDER);
    foreach ($imports as $import) {
        $modelClass = $import[1];
        $alias = $import[2] ?? substr($modelClass, strrpos($modelClass, '\\')+1);
        if (!preg_match('/\\b'.preg_quote($alias, '/').'(?:::|\\s+\\$)/', $body)) continue;
        try {
            $model = new $modelClass;
            $table = $model->getTable();
            $indexes = array_values(array_filter($metadata['indexes'], fn ($ix) => $ix['TABLE_NAME'] === $table));
            $scopes = [];
            foreach ((new ReflectionClass($modelClass))->getMethods() as $scope) {
                if (!str_starts_with($scope->name, 'scope')) continue;
                $call = lcfirst(substr($scope->name, 5));
                if (!preg_match('/(?:->|::)'.preg_quote($call, '/').'\\s*\\(/', $body)) continue;
                $scopeLines = file($scope->getFileName());
                $scopeBody = implode('', array_slice($scopeLines, $scope->getStartLine()-1, $scope->getEndLine()-$scope->getStartLine()+1));
                $scopes[] = ['method' => $scope->name, 'source' => str_replace('\\', '/', substr($scope->getFileName(), strlen($root)+1)),
                    'line' => $scope->getStartLine(), 'body' => $scopeBody];
            }
            $models[] = ['class' => $modelClass, 'table' => $table, 'table_present' => in_array($table, $metadata['tables']),
                'existing_indexes' => $indexes, 'called_scopes' => $scopes];
        } catch (Throwable $e) {
            $models[] = ['class' => $modelClass, 'inspection_error' => get_class($e)];
        }
    }
    $inspectionBody = $body;
    foreach ($models as $model) foreach ($model['called_scopes'] ?? [] as $scope) $inspectionBody .= "\n".$scope['body'];
    preg_match_all('/\$(?:request|data)->(?!filled\b|input\b|get\b|has\b|validate\b|all\b|user\b)(\w+)/', $inspectionBody, $props);
    preg_match_all('/(?:->(?:filled|input|get|has)|request)\s*\(\s*[\x27\x22]([^\x27\x22]+)[\x27\x22]/', $inspectionBody, $gets);
    $params = array_values(array_unique(array_merge($props[1], $gets[1])));
    $search = array_values(array_intersect($params, ['search', 'word', 'q', 'query']));
    $sort = array_values(array_filter($params, fn ($p) => str_contains($p, 'sort')));
    $paginationParams = array_values(array_intersect($params, ['page', 'per_page', 'limit']));
    $filter = array_values(array_diff($params, $search, $sort, $paginationParams));
    preg_match_all('/view\(\s*[\x27\x22]([^\x27\x22]+)[\x27\x22]/', $body, $views);
    $lineFilter = fn ($regex) => array_values(array_filter(array_map('trim', explode("\n", $inspectionBody)), fn ($l) => preg_match($regex, $l)));
    $middlewares = array_values(array_unique(array_map(fn ($m) => is_string($m) ? $m : 'INLINE MIDDLEWARE; inspect route source', array_merge(...array_map(fn ($r) => $r['middleware'], $routes ?: [['middleware' => []]])))));
    $needsUsers = count(array_filter($middlewares, fn ($m) => $m === 'auth' || $m === 'auth:web' || str_starts_with($m, 'auth:desktop'))) > 0;
    $missing = array_values(array_map(fn ($m) => $m['table'], array_filter($models, fn ($m) => isset($m['table_present']) && !$m['table_present'])));
    if ($needsUsers && !in_array('users', $metadata['tables'])) $missing[] = 'users';
    $missing = array_values(array_unique($missing));
    $status = $missing ? 'UNVERIFIED — ENVIRONMENT LIMITATION' : 'SOURCE-VERIFIED';
    if (str_contains($fqcn, 'YardController') || str_contains($fqcn, 'Api\\Superagent\\BookingContainerController')) $status = 'OUT OF SCOPE';
    $rows[] = [
        'resource' => preg_replace('/Controller$/', '', $class[1]), 'controller_action' => $action,
        'source' => $entry['source'].':'.$method->getStartLine(), 'routes' => $routes,
        'route_resolution' => $routes ? 'registered route collection' : 'NO REGISTERED ROUTE MATCH; method retained in source inventory',
        'consumer_interface' => $views[1] ?: (str_contains($fqcn, '\\Api\\') ? ['API consumers; registered routes above'] : ['See source; no literal view call']),
        'search_parameters' => $search, 'filter_parameters' => $filter, 'sort_parameters' => $sort,
        'ordering_source' => $lineFilter('/orderBy|latest\(|oldest\(|sortListing|sortBy/'),
        'pagination_parameters' => $paginationParams,
        'pagination_source' => $lineFilter('/paginate|Paginator|perPage|per_page|->get\(|::all\(/'),
        'authorization_mechanism' => $middlewares,
        'major_relationships_source' => $lineFilter('/with\(|withCount|withSum|withExists|whereHas|whereDoesntHave|->load/'),
        'models_and_existing_indexes' => $models,
        'phase2_modification' => in_array($action, $changed) ? 'YES' : 'NOT MODIFIED',
        'source_verification' => 'SOURCE-VERIFIED', 'runtime_verification' => $status,
        'query_verification' => in_array($action, $changed) ? 'QUERY VERIFIED; query-verification-result.json and controller-comparison.json' : 'NOT RUN; untouched source inventory',
        'missing_dependencies' => $missing,
        'limitations' => 'Expressions and directly referenced models/scopes inspected; dynamic relation chains and view accessors are not asserted to be a complete runtime dependency graph.',
    ];
}
echo json_encode(['listing_methods_inventoried' => count($rows), 'policy' => 'Imported database is source of truth; missing dependencies are LEGACY CODE / DATABASE MISMATCH',
    'resources' => $rows], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
