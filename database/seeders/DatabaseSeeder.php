<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // Create test user (legacy)
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seed school data in order (respecting relationships)
        $this->call([
            SchoolSeeder::class,
            SchoolContactSeeder::class,
            SchoolAddressSeeder::class,
            SchoolManagementSeeder::class,
            SchoolOfficialSeeder::class,
            SchoolDocumentSeeder::class,
            AcademicYearSeeder::class,
            SchoolClassSeeder::class,
        ]);

        // Keep legacy role seeder for backward compatibility (runs after new system)
        $this->call([
            UserRolesSeeder::class,
        ]);

        // Seed roles and permissions first (required before users)
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

        // Create sample users with proper roles (after schools are created)
        $this->call([
            SampleUsersSeeder::class,
        ]);
    }
}
