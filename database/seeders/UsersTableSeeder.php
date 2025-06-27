<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    private $pass = 'balitech';
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'Ali Bashir',
            'email' => 'abash17@admin.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('admin');

        $user = User::create([
            'name' => 'Mian Paul',
            'email' => 'mian@admin.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('admin');

        // Create some additional users
        $user = User::create([
            'name' => 'Ella Swift',
            'email' => 'ella5002@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');
        
        $user = User::create([
            'name' => 'Daniel Mitchell',
            'email' => 'daniel5003@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');

        $user = User::create([
            'name' => 'David Williams',
            'email' => 'david5001@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');


        $user = User::create([
            'name' => 'Peter johnson',
            'email' => 'peter5012@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');
        $user = User::create([
            'name' => 'Alex Wood	',
            'email' => 'alex5004@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');
        $user = User::create([
            'name' => 'Emma Watson',
            'email' => 'emma5013@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');
        $user = User::create([
            'name' => 'Ethen Oliver',
            'email' => 'ethen5020@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');
        $user = User::create([
            'name' => 'Mark Wilson',
            'email' => 'mark5005@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');
        $user = User::create([
            'name' => 'Josh Wood',
            'email' => 'josh5011@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');
        $user = User::create([
            'name' => 'Steve Parker	',
            'email' => 'steve5010@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');
        $user = User::create([
            'name' => 'Eric Jonson',
            'email' => 'eric5009@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');


        $user = User::create([
            'name' => 'Mike Davis',
            'email' => 'mike5014@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');
        $user = User::create([
            'name' => 'Sam Willson',
            'email' => 'sam5015@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');
        $user = User::create([
            'name' => 'Mark Wilson',
            'email' => 'mark5005@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');
        $user = User::create([
            'name' => 'John Spark',
            'email' => 'john4003@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');
        $user = User::create([
            'name' => 'Mike Davis',
            'email' => 'mike4001@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');
        $user = User::create([
            'name' => 'Travis Scott',
            'email' => 'travis4002@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');
        $user = User::create([
            'name' => 'Sofia Emilis',
            'email' => 'sofia5017@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');

        $user = User::create([
            'name' => 'John Herrison',
            'email' => 'john4005@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');
        $user = User::create([
            'name' => 'Jay Smith',
            'email' => 'jay4006@fronter.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');
    }
} 