<?php
require __DIR__ . '/../vendor/autoload.php';

$agent = json_decode(file_get_contents(__DIR__ . '/agent_routes.json'), true);
$superagent = json_decode(file_get_contents(__DIR__ . '/superagent_routes.json'), true);

function categorizeRoutes($routes, $role) {
    $categorized = [];
    foreach ($routes as $r) {
        $uri = $r['uri'];
        $method = $r['method'];
        $action = $r['action'];

        $type = 'Action / Mutation';
        $paginated = 'غير مطلوب (Action)';
        $reason = 'مسار تنفيذ عمليات (تسجيل دخول، اعتماد، إضافة، تحديث، إلغاء)';

        if ($method === 'GET' || str_contains($uri, 'fetch') || str_contains($uri, 'index') || str_contains($uri, 'photos')) {
            if (str_contains($uri, 'profile') || str_contains($uri, 'details') || str_contains($uri, 'image') || str_contains($uri, 'statistics') || str_contains($uri, 'wallets') || str_contains($uri, 'home')) {
                $type = 'Single Resource / Summary';
                $paginated = 'غير مطلوب (سجل مفرد / إحصائيات)';
                $reason = 'بيانات ملخصة، أرصدة محفظة، إحصائيات، أو سجل منفرد';
            } elseif (str_contains($uri, 'cities') || str_contains($uri, 'categories') || str_contains($uri, 'drivers') || str_contains($uri, 'cars') || ($uri === '/api/agent/fetch_yards') || ($uri === '/api/superagent/fetch_yards') || ($uri === '/api/superagent/fetch_active_yards')) {
                $type = 'Reference / Lookup';
                $paginated = 'غير مطلوب (قوائم مرجعية ثابتة)';
                $reason = 'بيانات مرجعية للقوائم المنسدلة (Dropdowns) محدودة العدد وثابتة';
            } elseif (str_contains($uri, 'assignments')) {
                $type = 'Active Daily Queue';
                $paginated = 'غير مطلوب (طابور يومي محدود)';
                $reason = 'تكليفات اليوم الميدانية المباشرة للمندوب (محدودة بتاريخ اليوم)';
            } else {
                $type = 'Collection / Listing';
                $paginated = 'تم تطبيق الترقيم (PAGINATED)';
                $reason = 'قائمة ديناميكية قابلة للتضخم؛ تم تطبيق SQL Pagination';
            }
        }

        // Specific overrides based on implementation
        if (in_array($uri, [
            '/api/agent/fetch_delivery_policies',
            '/api/agent/fetch_all_expenses',
            '/api/agent/fetch_your_notifications',
            '/api/agent/photos',
            '/api/agent/booking/fetch_bookings',
            '/api/agent/fetch_agents',
            '/api/agent/fetch_yard_bookings',
            '/api/agent/booking/fetch_booking_containers',
            '/api/superagent/booking/fetch_agents',
            '/api/superagent/fetch_your_notifications',
            '/api/superagent/fetch_yard_bookings',
            '/api/superagent/booking/specification',
            '/api/superagent/booking/waiting',
            '/api/superagent/booking/loading',
            '/api/superagent/booking/unloading',
            '/api/superagent/booking/missions/all',
            '/api/superagent/booking/pending_stage_receipts',
        ])) {
            $paginated = 'تم تطبيق الترقيم (PAGINATED)';
            $type = 'Collection / Listing';
            $reason = 'تم تفعيل الترقيم الكامل وإرجاع كائن pagination المستقل';
        }

        $categorized[] = [
            'role' => $role,
            'method' => $method,
            'uri' => $uri,
            'action' => class_basename($action),
            'type' => $type,
            'pagination' => $paginated,
            'reason' => $reason,
        ];
    }
    return $categorized;
}

$agentCat = categorizeRoutes($agent, 'Agent');
$superCat = categorizeRoutes($superagent, 'Superagent');

file_put_contents(__DIR__ . '/agent_categorized.json', json_encode($agentCat, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents(__DIR__ . '/superagent_categorized.json', json_encode($superCat, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Categorized saved successfully.\n";
