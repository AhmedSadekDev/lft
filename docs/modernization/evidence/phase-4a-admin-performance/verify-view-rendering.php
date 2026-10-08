<?php
require __DIR__.'/phase4a-bootstrap.php';

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashbaordController;
use Illuminate\Http\Request;

echo "=== VIEW RENDERING VERIFICATION ===\n\n";

$mockUser = new class extends \Illuminate\Foundation\Auth\User {
    public $roles;
    public function __construct() { $this->roles = collect([]); }
    public function can($ability, $arguments = []) { return true; }
    public function hasPermissionTo($permission, $guardName = null) { return true; }
    public function hasRole($roles, $guard = null) { return true; }
};
auth()->setUser($mockUser);

// 1. Dashboard View
try {
    $dashController = $app->make(DashbaordController::class);
    $dashReq = Request::create('/admin', 'GET');
    $dashView = $dashController($dashReq);
    
    echo "[Dashboard View]\n";
    echo "  View Name: " . $dashView->name() . "\n";
    echo "  Stats Present: " . (isset($dashView->getData()['stats']) ? 'YES' : 'NO') . "\n";
    echo "  Bookings Chart Labels: " . count($dashView->getData()['bookingsChart']['labels']) . "\n";
    echo "  Financial Chart Labels: " . count($dashView->getData()['financialChart']['labels']) . "\n";
    echo "  Rendered HTML bytes: " . strlen($dashView->render()) . "\n";
    echo "  Status: SUCCESS\n\n";
} catch (\Throwable $e) {
    echo "[Dashboard View] FAILED: " . $e->getMessage() . "\n\n";
}

// 2. Bookings Index View
try {
    $bookingController = $app->make(BookingController::class);
    $bookingReq = Request::create('/admin/bookings', 'GET');
    $bookingView = $bookingController->index($bookingReq);
    
    echo "[Bookings Index View]\n";
    echo "  View Name: " . $bookingView->name() . "\n";
    echo "  Bookings Count: " . $bookingView->getData()['bookings']->count() . "\n";
    echo "  Stage Counts: " . json_encode($bookingView->getData()['stageCounts']) . "\n";
    echo "  Companies Dropdown Count: " . $bookingView->getData()['companies']->count() . "\n";
    echo "  Rendered HTML bytes: " . strlen($bookingView->render()) . "\n";
    echo "  Status: SUCCESS\n\n";
} catch (\Throwable $e) {
    echo "[Bookings Index View] FAILED: " . $e->getMessage() . "\n\n";
}
