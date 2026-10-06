<?php
// Verification bootstrap for Phase 3. Server-enforced READ ONLY session.
$root = dirname(__DIR__, 4);
require $root.'/vendor/autoload.php';

foreach ([
    'APP_ENV' => 'phase3_verification',
    'APP_CONFIG_CACHE' => $root.'/storage/app/phase3_no_cached_config.php',
    'TELESCOPE_ENABLED' => 'false',
    'CACHE_DRIVER' => 'array',
    'SESSION_DRIVER' => 'array'
] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}

$app = require $root.'/bootstrap/app.php';
$app->afterBootstrapping(Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
    $app['config']->set('cache.default', 'array');
    $app['config']->set('session.driver', 'array');
    $app['config']->set('telescope.enabled', false);
    $app['config']->set('database.connections.mysql.options', [
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET SESSION TRANSACTION READ ONLY',
    ]);
});

$app->afterBootstrapping(Illuminate\Foundation\Bootstrap\RegisterProviders::class, function ($app) {
    $connection = $app['db']->connection();
    if ($connection->getDatabaseName() !== 'leader') {
        throw new RuntimeException('Wrong target database');
    }
    $connection->beginTransaction();
});

$app->instance('request', Illuminate\Http\Request::create('http://localhost/'));
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
$db = $app['db']->connection();

register_shutdown_function(function () use ($db) {
    if ($db->transactionLevel()) {
        $db->rollBack();
    }
});
