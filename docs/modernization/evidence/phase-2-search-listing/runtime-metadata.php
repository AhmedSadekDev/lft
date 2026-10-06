<?php
require __DIR__.'/verification-bootstrap.php';
$routes = [];
foreach ($app['router']->getRoutes() as $route) {
    $routes[] = ['uri' => '/'.$route->uri(), 'methods' => $route->methods(), 'name' => $route->getName(),
        'action' => $route->getActionName(), 'middleware' => $route->gatherMiddleware()];
}
$pdo = $db->getPdo();
$tables = $pdo->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetchAll(PDO::FETCH_COLUMN);
$indexes = $pdo->query('SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX')->fetchAll(PDO::FETCH_ASSOC);
$columns = $pdo->query('SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME, ORDINAL_POSITION')->fetchAll(PDO::FETCH_ASSOC);
$binlog = $pdo->query('SHOW BINARY LOG STATUS')->fetch(PDO::FETCH_ASSOC);
echo json_encode(['routes' => $routes, 'tables' => $tables, 'indexes' => $indexes, 'columns' => $columns,
    'binlog' => $binlog, 'isolation' => 'session transaction read only; transaction opened before provider boot'],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
