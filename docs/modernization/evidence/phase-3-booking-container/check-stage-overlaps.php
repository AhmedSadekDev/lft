<?php
require __DIR__.'/phase3-bootstrap.php';

// Check if any container can match multiple stages simultaneously
$overlaps = $db->select("
SELECT id,
  (CASE WHEN status = 0 OR (status = 1 AND superagent_specification_approved = 0) THEN 1 ELSE 0 END) as is_spec,
  (CASE WHEN superagent_specification_approved = 1 AND is_in_loading = 0 AND superagent_loading_approved = 0 AND superagent_unloading_approved = 0 THEN 1 ELSE 0 END) as is_waiting,
  (CASE WHEN superagent_specification_approved = 1 AND is_in_loading = 1 AND superagent_loading_approved = 0 AND superagent_unloading_approved = 0 THEN 1 ELSE 0 END) as is_loading,
  (CASE WHEN superagent_specification_approved = 1 AND superagent_loading_approved = 1 AND superagent_unloading_approved = 0 THEN 1 ELSE 0 END) as is_unloading
FROM booking_containers
HAVING (is_spec + is_waiting + is_loading + is_unloading) > 1
");

echo "Overlapping containers count: " . count($overlaps) . "\n";
