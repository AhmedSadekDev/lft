<?php

return [
    // Bookings created before this moment keep the export path whatever their type (in-flight data).
    // Set it to the deployment time; an empty value applies the paths to every booking.
    'effective_from' => env('WORKFLOWS_EFFECTIVE_FROM', '2026-10-16 00:00:00'),
];
