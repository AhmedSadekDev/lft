<?php
require __DIR__.'/phase3-bootstrap.php';

$nonNull = $db->table('bookings')->whereNotNull('yard_id')->count();
$null = $db->table('bookings')->whereNull('yard_id')->count();
$distinctYards = $db->table('bookings')->whereNotNull('yard_id')->distinct()->pluck('yard_id')->all();

echo "Bookings with non-null yard_id: $nonNull\n";
echo "Bookings with null yard_id: $null\n";
echo "Distinct yard_ids: " . implode(', ', $distinctYards) . "\n";
