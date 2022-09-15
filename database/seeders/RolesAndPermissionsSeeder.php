<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        Permission::create(['name' => 'create_loan']);
        Permission::create(['name' => 'approve_loan']);
        Permission::create(['name' => 'view_loan']);
        Permission::create(['name' => 'repay_loan']);
        Permission::create(['name' => 'view_loan_list']);

        // create roles and assign created permissions

        // this can be done as separate statements
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo(['approve_loan', 'view_loan', 'view_loan_list']);

        // or may be done by chaining
        $role = Role::create(['name' => 'customer'])
            ->givePermissionTo(['create_loan', 'view_loan', 'repay_loan', 'view_loan_list']);

        $role = Role::create(['name' => 'super-admin']);
        $role->givePermissionTo(Permission::all());
    }
}
