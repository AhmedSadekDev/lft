<?php
$agent = json_decode(file_get_contents(__DIR__ . '/agent_inventory.json'), true);
$super = json_decode(file_get_contents(__DIR__ . '/superagent_inventory.json'), true);

echo "--- AGENT ROUTES ---\n";
foreach ($agent as $r) {
    if (str_contains($r['status'], 'PAGINATED') || $r['type'] === 'Other / Specific') {
        echo "{$r['method']} {$r['uri']} -> {$r['action']} [{$r['status']}]\n";
    }
}

echo "\n--- SUPERAGENT ROUTES ---\n";
foreach ($super as $r) {
    if (str_contains($r['status'], 'PAGINATED') || $r['type'] === 'Other / Specific') {
        echo "{$r['method']} {$r['uri']} -> {$r['action']} [{$r['status']}]\n";
    }
}
