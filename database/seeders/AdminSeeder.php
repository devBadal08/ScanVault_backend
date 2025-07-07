<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // make sure role exists
        $role = Role::firstOrCreate(['name' => 'Super Admin']);

        // create user
        $admin = User::updateOrCreate(
            ['email' => 'superadmin@superadmin.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('123456'),
                'role' => 'Super Admin',
            ]
        );

        // assign role
        $admin->assignRole($role);
    }
}
