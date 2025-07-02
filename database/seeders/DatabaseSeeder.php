<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    private $pass = 'balitech';
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $this->call([
        //     RolesTableSeeder::class,
        //     UsersTableSeeder::class,
        // ]);

        $user = User::create([
            'name' => 'Angela',
            'email' => 'angela@closer.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');

        $user = User::create([
            'name' => 'Ken Lee',
            'email' => 'ken@closer.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');

        $user = User::create([
            'name' => 'Simon Jones',
            'email' => 'simon@closer.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');

        $user = User::create([
            'name' => 'Ronnie Adams',
            'email' => 'ronnie@closer.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');
        
        $user = User::create([
            'name' => 'Robert Hill',
            'email' => 'robert@closer.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');

        $user = User::create([
            'name' => 'Matt Henry',
            'email' => 'matt@closer.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');

        $user = User::create([
            'name' => 'Hassan Shahzad',
            'email' => 'hassan@qa.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');

        $user = User::create([
            'name' => 'Janette Amir',
            'email' => 'janette@closer.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');

        $user = User::create([
            'name' => 'Haseeb Sajjad',
            'email' => 'haseeb@dev.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');

        $user = User::create([
            'name' => 'Rachel Smith',
            'email' => 'rachel@retention.com',
            'password' => Hash::make($this->pass),
        ]);
        $user->assignRole('user');
    }
}
