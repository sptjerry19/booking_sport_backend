<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting database seeding...');

        $this->call([
            RolePermissionSeeder::class,  // Create roles, permissions & demo users
            SportSeeder::class,           // Create 20 sports
            PlayerSeeder::class,          // Create 200 player users
            VenueSeeder::class,          // Create venues, courts, pricing, slots, bookings
        ]);

        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->info('📊 Expected total records: ~10,000+');
        $this->command->info('🔑 Demo accounts:');
        $this->command->info('   - admin@example.com / password (Admin)');
        $this->command->info('   - owner@example.com / password (Owner)');
        $this->command->info('   - player@example.com / password (Player)');
    }
}
