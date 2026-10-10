<?php
$content = file_get_contents(__DIR__ . '/artisan_test_output.txt');
$lines = explode("\n", $content);

$currentFile = '';
$fileResults = [];

foreach ($lines as $line) {
    $clean = trim(preg_replace('/\x1b\[[0-9;]*m/', '', $line));
    if (preg_match('/^(PASS|FAIL)\s+(.*)$/', $clean, $m)) {
        $status = $m[1];
        $file = $m[2];
        $currentFile = $file;
        $fileResults[$file] = ['status' => $status, 'tests' => []];
    } elseif ($currentFile && preg_match('/^[✓⨯]\s+(.*)$/u', $clean, $m)) {
        $testName = $m[1];
        $pass = str_starts_with($clean, '✓');
        $fileResults[$currentFile]['tests'][] = [
            'name' => $testName,
            'pass' => $pass
        ];
    }
}

foreach ($fileResults as $file => $data) {
    $total = count($data['tests']);
    $passed = count(array_filter($data['tests'], fn($t) => $t['pass']));
    $failed = $total - $passed;
    echo "File: $file | Status: {$data['status']} | Total: $total | Passed: $passed | Failed: $failed\n";
    if ($failed > 0) {
        foreach ($data['tests'] as $t) {
            if (!$t['pass']) {
                echo "   -> FAIL: {$t['name']}\n";
            }
        }
    }
}
