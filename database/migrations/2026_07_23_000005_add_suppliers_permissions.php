<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Seed suppliers permissions without truncating existing ones.
     * Controllers use the legacy "udpate" typo to match the rest of the admin module.
     */
    public function up()
    {
        $permissions = [
            'suppliers.index',
            'suppliers.create',
            'suppliers.udpate',
            'suppliers.update',
            'suppliers.delete',
        ];

        $created = [];

        foreach ($permissions as $name) {
            $created[] = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );
        }

        $adminRole = Role::where('name', 'Admin')->where('guard_name', 'web')->first();

        if ($adminRole) {
            $adminRole->givePermissionTo($created);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down()
    {
        $names = [
            'suppliers.index',
            'suppliers.create',
            'suppliers.udpate',
            'suppliers.update',
            'suppliers.delete',
        ];

        DB::table('permissions')->whereIn('name', $names)->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
