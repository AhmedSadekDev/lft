<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

$existing = DB::table('permissions')
    ->where('name', 'e_invoices.index')
    ->where('guard_name', 'web')
    ->first();

if ($existing) {
    $permissionId = (int) $existing->id;
    echo "exists id={$permissionId}\n";
} else {
    $nextId = (int) DB::table('permissions')->max('id') + 1;
    $now = now()->toDateTimeString();
    DB::table('permissions')->insert([
        'id' => $nextId,
        'name' => 'e_invoices.index',
        'guard_name' => 'web',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $permissionId = $nextId;
    echo "inserted id={$permissionId}\n";
}

$adminRoleId = DB::table('roles')
    ->where('name', 'Admin')
    ->where('guard_name', 'web')
    ->value('id');

if ($adminRoleId) {
    $linked = DB::table('role_has_permissions')
        ->where('permission_id', $permissionId)
        ->where('role_id', $adminRoleId)
        ->exists();

    if (! $linked) {
        DB::table('role_has_permissions')->insert([
            'permission_id' => $permissionId,
            'role_id' => $adminRoleId,
        ]);
        echo "assigned to Admin role_id={$adminRoleId}\n";
    } else {
        echo "already assigned to Admin role_id={$adminRoleId}\n";
    }
} else {
    echo "Admin role not found\n";
}

app(PermissionRegistrar::class)->forgetCachedPermissions();
echo "cache_cleared\n";
