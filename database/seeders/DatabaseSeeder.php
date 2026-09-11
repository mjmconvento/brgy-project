<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with two signed-in-able accounts and a
     * realistic barangay data set.
     *
     * Both accounts go through the factory so the `hashed` cast on `password`
     * applies.
     */
    public function run(): void
    {
        User::factory()->create([
            'first_name' => 'Barangay',
            'middle_name' => 'Santos',
            'last_name' => 'Admin',
            'email' => 'admin@brgy.local',
            'password' => 'password',
        ]);

        User::factory()->create([
            'first_name' => 'Test',
            'middle_name' => 'Dela Cruz',
            'last_name' => 'User',
            'email' => 'test1@user.com',
            'password' => 'password112233',
        ]);

        $this->call(BarangaySeeder::class);
    }
}
