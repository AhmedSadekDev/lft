<?php
require __DIR__.'/phase4a-bootstrap.php';
require_once __DIR__.'/test-dashboard-optimization.php';

echo "\n--- Value-Equivalence Check (numeric equal) ---\n";
$statsEqual = true;
foreach ($legData['stats'] as $k => $v) {
    if ($v != $optData['stats'][$k]) {
        echo "Value mismatch on $k: legacy=$v vs opt={$optData['stats'][$k]}\n";
        $statsEqual = false;
    }
}
echo "Numeric stats match: " . ($statsEqual ? "100% IDENTICAL" : "FAILED") . "\n";

$fcEqual = true;
foreach (['expenses', 'income'] as $type) {
    for ($i = 0; $i < count($legData['financialChart'][$type]); $i++) {
        if ($legData['financialChart'][$type][$i] != $optData['financialChart'][$type][$i]) {
            echo "FC mismatch at index $i: leg=" . $legData['financialChart'][$type][$i] . " vs opt=" . $optData['financialChart'][$type][$i] . "\n";
            $fcEqual = false;
        }
    }
}
echo "Numeric financial chart match: " . ($fcEqual ? "100% IDENTICAL" : "FAILED") . "\n";
