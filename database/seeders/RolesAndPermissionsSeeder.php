<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Schema::disableForeignKeyConstraints();
        DB::table('roles')->truncate();

        DB::table('permissions')->truncate();

        DB::table('role_has_permissions')->truncate();

        DB::table('model_has_permissions')->truncate();

        DB::table('model_has_roles')->truncate();
        Schema::enableForeignKeyConstraints();

        /**
         *  create new role
         */
        $adminRole = Role::updateOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        $admin = User::where('email', 'admin@admin.com')->first();
        if (is_null($admin)) {
            $admin = User::updateOrCreate(['name' => 'Admin', 'email' => 'admin@admin.com'], [
                'name'              => 'Admin',
                'email'             => 'admin@admin.com',
                'password'          => '123456789',
                'email_verified_at' => now(),
            ]);

            if ($adminRole)
                $admin->syncRoles('Admin');
        }


        /**
         *  get database tables
         */
        $dbTables = adminDbTablesPermissions();


        /**
         *  give permission on every database table
         */

        foreach ($dbTables as $table) {
            // admin permission
            $readPermission   = Permission::create(['name' => $table . '.index', 'guard_name' => 'web']);
            $createPermission = Permission::create(['name' =>  $table . '.create', 'guard_name' => 'web']);
            $updatePermission = Permission::create(['name' =>  $table . '.update', 'guard_name' => 'web']);
            $deletePermission = Permission::create(['name' =>  $table . '.delete', 'guard_name' => 'web']);

            /**
             * assign permission to role
             */
            $adminRole->givePermissionTo($readPermission, $createPermission, $updatePermission, $deletePermission);

            /**
             * assign permission to user
             */
            // if ($admin) {
            //     $admin->givePermissionTo($readPermission, $createPermission, $updatePermission, $deletePermission);
            // }
        }
    }
}
