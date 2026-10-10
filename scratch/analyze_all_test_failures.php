<?php
exec('php artisan test', $output, $code);
$failures = [];
$capture = false;
$currentTest = '';

foreach ($output as $line) {
    if (preg_match('/^  • (.*)$/', $line, $m)) {
        $currentTest = trim($m[1]);
        $failures[$currentTest] = [];
    } elseif ($currentTest && preg_match('/^(Failed asserting|ErrorException|PDOException|Error|Exception|SQLSTATE)/', trim($line))) {
        $failures[$currentTest][] = trim($line);
    }
}

echo "Total Failures Captured: " . count($failures) . "\n";
foreach ($failures as $test => $reasons) {
    echo "- $test\n";
    if (!empty($reasons)) {
        echo "  " . $reasons[0] . "\n";
    }
}
file_put_contents(__DIR__ . '/test_failures_breakdown.json', json_encode($failures, JSON_PRETTY_PRINT));
