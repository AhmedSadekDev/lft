<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$perms = Spatie\Permission\Models\Permission::orderBy('name')->pluck('name');
$ar = include __DIR__ . '/../resources/lang/ar/admin.php';

$missing = [];
$ok = [];
foreach ($perms as $name) {
    if (array_key_exists($name, $ar)) {
        $ok[] = $name;
    } else {
        $missing[] = $name;
    }
}

echo "total={$perms->count()} ok=" . count($ok) . " missing=" . count($missing) . PHP_EOL;
foreach ($missing as $m) {
    echo $m . PHP_EOL;
}
