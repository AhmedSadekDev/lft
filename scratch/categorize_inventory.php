<?php
$agentRoutes = json_decode(file_get_contents(__DIR__ . '/all_agent_routes.json'), true);
$superagentRoutes = json_decode(file_get_contents(__DIR__ . '/all_superagent_routes.json'), true);

$paginatedUris = [
    // Agent
    'POST /api/agent/fetch_your_notifications',
    'GET /api/agent/photos',
    'POST /api/agent/photos', // index/search fallback with store
    'GET /api/agent/fetch_delivery_policies',
    'POST /api/agent/fetch_all_expenses',
    'POST /api/agent/fetch_agents',
    'POST /api/agent/booking/fetch_bookings',
    'GET /api/agent/booking/fetch_yard_bookings',
    
    // Superagent
    'POST /api/superagent/fetch_your_notifications',
    'POST /api/superagent/booking/fetch_agents',
    'GET /api/superagent/booking/specification',
    'GET /api/superagent/booking/waiting',
    'GET /api/superagent/booking/loading',
    'GET /api/superagent/booking/unloading',
    'GET /api/superagent/booking/all',
    'GET /api/superagent/booking/fetch_yard_bookings',
    'GET /api/superagent/booking/pending_stage_receipts',
];

function categorize($method, $uri, $action, $paginatedUris) {
    $key = "$method $uri";
    if (in_array($key, $paginatedUris)) {
        return ['status' => 'PAGINATED (مُرقم مع pagination object)', 'type' => 'Collection / List'];
    }
    
    // Check Single Resource / Details
    if (
        str_contains($uri, '{') || 
        str_contains($uri, 'fetch_profile') || 
        str_contains($uri, 'wallets') || 
        str_contains($uri, 'show_') || 
        str_contains($uri, 'receipts_count') || 
        str_contains($uri, 'receipt_summary') ||
        str_contains($uri, 'find_') ||
        str_contains($uri, 'fetch_one_') ||
        str_contains($uri, 'agent_details') ||
        str_contains($uri, 'booking_details')
    ) {
        return ['status' => 'EXCLUDED (سجل مفرد أو تفاصيل/إحصائيات)', 'type' => 'Single Resource / Details'];
    }
    
    // Check Actions / Mutations
    if (
        str_contains($uri, 'login') || 
        str_contains($uri, 'password') || 
        str_contains($uri, 'Otp') || 
        str_contains($uri, 'store') || 
        str_contains($uri, 'update') || 
        str_contains($uri, 'delete') || 
        str_contains($uri, 'destroy') || 
        str_contains($uri, 'charge-cars-wallet') || 
        str_contains($uri, 'save_') || 
        str_contains($uri, 'apply_') || 
        str_contains($uri, 'confirm_') || 
        str_contains($uri, 'accept_') || 
        str_contains($uri, 'reject_') || 
        str_contains($uri, 'done_') || 
        str_contains($uri, 'close_') || 
        str_contains($uri, 'transfer_') || 
        str_contains($uri, 'assign_') || 
        str_contains($uri, 'add_') || 
        str_contains($uri, 'create_') || 
        str_contains($uri, 'return_') || 
        str_contains($uri, 'refuse_') || 
        str_contains($uri, 'cancel_')
    ) {
        return ['status' => 'EXCLUDED (عملية إجراء أو تعديل بيانات Action/Mutation)', 'type' => 'Action / Mutation'];
    }
    
    // Lookups
    if (
        str_contains($uri, 'fetch_status') || 
        str_contains($uri, 'fetch_types') || 
        str_contains($uri, 'fetch_reasons') ||
        str_contains($uri, 'fetch_all_notes')
    ) {
        return ['status' => 'EXCLUDED (قاموس ثوابت خفيف Small Lookup)', 'type' => 'Lookup / Dict'];
    }

    return ['status' => 'EXCLUDED (محدد النطاق / عملية محددة)', 'type' => 'Other / Specific'];
}

$agentTable = [];
foreach ($agentRoutes as $r) {
    $cat = categorize($r['method'], $r['uri'], $r['action'], $paginatedUris);
    $agentTable[] = array_merge($r, $cat);
}

$superagentTable = [];
foreach ($superagentRoutes as $r) {
    $cat = categorize($r['method'], $r['uri'], $r['action'], $paginatedUris);
    $superagentTable[] = array_merge($r, $cat);
}

file_put_contents(__DIR__ . '/agent_inventory.json', json_encode($agentTable, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
file_put_contents(__DIR__ . '/superagent_inventory.json', json_encode($superagentTable, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "Generated inventories successfully.\n";
