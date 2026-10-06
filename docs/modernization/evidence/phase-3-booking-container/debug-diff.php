<?php
require __DIR__.'/phase3-bootstrap.php';
require_once __DIR__.'/compare-resource-json.php';

// compare keys
foreach ($legacyJson as $k => $v) {
    if (!isset($sqlJson[$k])) {
        echo "Missing key in SQL: $k\n";
    } elseif ($v != $sqlJson[$k]) {
        echo "Difference in top key: $k\n";
        if ($k === 'data') {
            for ($i = 0; $i < count($v); $i++) {
                if ($v[$i] != $sqlJson['data'][$i]) {
                    echo "Difference at data index $i (Booking ID Legacy: {$v[$i]['id']}, SQL: {$sqlJson['data'][$i]['id']}):\n";
                    foreach ($v[$i] as $subK => $subV) {
                        if ($subV != $sqlJson['data'][$i][$subK]) {
                            echo "  Sub-key difference: $subK\n";
                            echo "    Legacy: " . json_encode($subV) . "\n";
                            echo "    SQL:    " . json_encode($sqlJson['data'][$i][$subK]) . "\n";
                        }
                    }
                }
            }
        } elseif ($k === 'links') {
            echo "Legacy links: " . json_encode($v) . "\n";
            echo "SQL links:    " . json_encode($sqlJson[$k]) . "\n";
        } elseif ($k === 'meta') {
            echo "Legacy meta: " . json_encode($v) . "\n";
            echo "SQL meta:    " . json_encode($sqlJson[$k]) . "\n";
        }
    }
}
