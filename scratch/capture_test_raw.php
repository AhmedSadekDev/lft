<?php
$raw = shell_exec('php artisan test');
file_put_contents(__DIR__ . '/artisan_test_output.txt', $raw);
echo "Length: " . strlen($raw) . "\n";
echo substr($raw, -500);
