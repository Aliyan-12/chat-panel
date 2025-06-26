<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create([
            'name' => 'Can Create Groups',
            'guard_name' => 'web'
        ]);
        Permission::create([
            'name' => 'Can Add Users',
            'guard_name' => 'web'
        ]);
        Permission::create([
            'name' => 'Can Delete Users',
            'guard_name' => 'web'
        ]);

        
        $admin = Role::create([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);
        $admin->givePermissionTo($this->getUserPermissions($admin->name));

        $user = Role::create([
            'name' => 'user',
            'guard_name' => 'web'
        ]);
        $user->givePermissionTo($this->getUserPermissions($user->name));
    }

    private function getUserPermissions(string $name)
    {
        if($name == 'admin') {
            return Permission::all();
        }
        if($name == 'user') {
            return ['Can Mark Attendance', 'Can View Tasks', 'Can Add Tasks', 'Can Edit Tasks', 'Can Delete Tasks'];
        }
    }
} 