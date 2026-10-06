<?php
require __DIR__.'/phase3-bootstrap.php';

$cols = array_map(fn($c) => $c->Field, $db->select('SHOW COLUMNS FROM booking_containers'));
$hasStageType = in_array('stage_type', $cols);

$relevantCols = array_values(array_filter($cols, fn($c) => 
    str_contains($c, 'stage') || 
    str_contains($c, 'status') || 
    str_contains($c, 'approved') || 
    str_contains($c, 'loading')
));

$counts = [
    'total_booking_containers' => $db->table('booking_containers')->count(),
    'has_stage_type_column' => $hasStageType,
    'relevant_columns' => $relevantCols,
];

// Count rows matching each stage condition
$counts['specification_count'] = $db->table('booking_containers')
    ->where(function ($q) {
        $q->where('status', 0)
          ->orWhere(function($q2) {
              $q2->where('status', 1)
                 ->where('superagent_specification_approved', 0);
          });
    })->count();

$counts['waiting_count'] = $db->table('booking_containers')
    ->where('superagent_specification_approved', 1)
    ->where('is_in_loading', 0)
    ->where('superagent_loading_approved', 0)
    ->where('superagent_unloading_approved', 0)
    ->count();

$counts['loading_count'] = $db->table('booking_containers')
    ->where('superagent_specification_approved', 1)
    ->where('is_in_loading', 1)
    ->where('superagent_loading_approved', 0)
    ->where('superagent_unloading_approved', 0)
    ->count();

$counts['unloading_count'] = $db->table('booking_containers')
    ->where('superagent_specification_approved', 1)
    ->where('superagent_loading_approved', 1)
    ->where('superagent_unloading_approved', 0)
    ->count();

echo json_encode($counts, JSON_PRETTY_PRINT);
