<?php
require __DIR__.'/phase3-bootstrap.php';

$conditionSql = "
(
  (`status` = 0 OR (`status` = 1 AND `superagent_specification_approved` = 0))
  OR (`superagent_specification_approved` = 1 AND `is_in_loading` = 0 AND `superagent_loading_approved` = 0 AND `superagent_unloading_approved` = 0)
  OR (`superagent_specification_approved` = 1 AND `is_in_loading` = 1 AND `superagent_loading_approved` = 0 AND `superagent_unloading_approved` = 0)
  OR (`superagent_specification_approved` = 1 AND `superagent_loading_approved` = 1 AND `superagent_unloading_approved` = 0)
)
";

$q1 = "
EXPLAIN SELECT count(*) as aggregate 
FROM `bookings` 
WHERE NOT EXISTS (SELECT * FROM `invoices` WHERE `bookings`.`id` = `invoices`.`booking_id`)
  AND EXISTS (
    SELECT * FROM `booking_containers` 
    WHERE `bookings`.`id` = `booking_containers`.`booking_id` 
      AND $conditionSql
  )
";

$q2 = "
EXPLAIN SELECT * 
FROM `bookings` 
WHERE NOT EXISTS (SELECT * FROM `invoices` WHERE `bookings`.`id` = `invoices`.`booking_id`)
  AND EXISTS (
    SELECT * FROM `booking_containers` 
    WHERE `bookings`.`id` = `booking_containers`.`booking_id` 
      AND $conditionSql
  )
ORDER BY `id` DESC
LIMIT 10 OFFSET 0
";

$q3 = "
EXPLAIN SELECT *, 
  CASE
    WHEN `superagent_specification_approved` = 1 AND `superagent_loading_approved` = 1 AND `superagent_unloading_approved` = 0 THEN 'unloading'
    WHEN `superagent_specification_approved` = 1 AND `is_in_loading` = 1 AND `superagent_loading_approved` = 0 AND `superagent_unloading_approved` = 0 THEN 'loading'
    WHEN `superagent_specification_approved` = 1 AND `is_in_loading` = 0 AND `superagent_loading_approved` = 0 AND `superagent_unloading_approved` = 0 THEN 'waiting'
    WHEN `status` = 0 OR (`status` = 1 AND `superagent_specification_approved` = 0) THEN 'specification'
    ELSE NULL
  END AS `stage_type`
FROM `booking_containers`
WHERE `booking_containers`.`booking_id` IN (509, 508, 507, 506, 505, 504, 503, 502, 501, 500)
  AND $conditionSql
ORDER BY `id` DESC
";

$plans = [
    'count_query' => $db->select($q1),
    'page_query' => $db->select($q2),
    'eager_load_containers_query' => $db->select($q3),
];

echo json_encode($plans, JSON_PRETTY_PRINT);
