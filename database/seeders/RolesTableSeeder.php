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
            'name' => 'can create groups',
            'guard_name' => 'web'
        ]);
        Permission::create([
            'name' => 'can add group-users',
            'guard_name' => 'web'
        ]);
        Permission::create([
            'name' => 'can delete group-users',
            'guard_name' => 'web'
        ]);

        Permission::create([
            'name' => 'can create users',
            'guard_name' => 'web'
        ]);
        Permission::create([
            'name' => 'can edit users',
            'guard_name' => 'web'
        ]);
        Permission::create([
            'name' => 'can delete users',
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
            return ['can create groups', 'can add group-users', 
            'can delete group-users', 'can create users', 
            'can edit users', 'can delete users'];
        }
    }
} 