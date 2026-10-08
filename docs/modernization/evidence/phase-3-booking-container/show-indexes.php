<?php
require __DIR__.'/phase3-bootstrap.php';

$indexes = $db->select('SHOW INDEX FROM booking_containers');
$grouped = [];
foreach ($indexes as $idx) {
    $grouped[$idx->Key_name][] = $idx->Column_name;
}

echo json_encode($grouped, JSON_PRETTY_PRINT) . PHP_EOL;
