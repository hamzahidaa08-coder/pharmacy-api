<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin/Pharmacy User
        User::factory()->create([
            'name' => 'Pharmacy Admin',
            'email' => 'pharmacy@example.com',
            'role' => 'pharmacy',
            'password' => bcrypt('password'),
        ]);

        // Create Delivery User
        User::factory()->create([
            'name' => 'Delivery Agent',
            'email' => 'delivery@example.com',
            'role' => 'delivery',
            'password' => bcrypt('password'),
        ]);

        // Create Client User
        User::factory()->create([
            'name' => 'Sarah Client',
            'email' => 'sarah@example.com',
            'role' => 'client',
            'password' => bcrypt('password'),
            'date_of_birth' => '1995-05-05',
        ]);

        // Seed Medicines
        $this->call(MedicineSeeder::class);
    }
}
