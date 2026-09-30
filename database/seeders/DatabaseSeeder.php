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
        // Seed standard RAG thresholds
        $this->call(SettingSeeder::class);

        // Seed initial projects
        $this->call(ProjectSeeder::class);

        // Seed baseline audit data (AUD-001)
        $this->call(AuditSeeder::class);

        // Seed default administrator user for Filament panel
        User::firstOrCreate(
            ['email' => 'admin@dqa.local'],
            [
                'name' => 'MEAL Administrator',
                'password' => bcrypt('password'),
            ]
        );
    }
}
