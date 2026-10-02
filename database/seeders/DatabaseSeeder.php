<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed standard RAG thresholds
        $this->call(SettingSeeder::class);

        // 2. Seed default users with operational roles
        // MEAL Officer (Full system administration & governance)
        $mealOfficer = User::updateOrCreate(
            ['email' => 'admin@dqa.local'],
            [
                'name' => 'MEAL Administrator',
                'password' => bcrypt('password'),
                'roles' => [User::ROLE_MEAL_OFFICER],
                'is_active' => true,
            ]
        );

        // Project Officer 1 (Samalani Ana Project Officer)
        $projectOfficer1 = User::updateOrCreate(
            ['email' => 'officer@dqa.local'],
            [
                'name' => 'J. Mwila (Project Officer)',
                'password' => bcrypt('password'),
                'roles' => [User::ROLE_PROJECT_OFFICER],
                'is_active' => true,
            ]
        );

        // Dual-Role Peer Auditor (Project Officer for Project A, but also Peer Auditor for other projects)
        $peerAuditor = User::updateOrCreate(
            ['email' => 'peer@dqa.local'],
            [
                'name' => 'K. Tembo (Peer Reviewer & PO)',
                'password' => bcrypt('password'),
                'roles' => [User::ROLE_PROJECT_OFFICER, User::ROLE_AUDITOR],
                'is_active' => true,
            ]
        );

        // Field Auditor (Field Auditor for assignments)
        $fieldAuditor = User::updateOrCreate(
            ['email' => 'auditor@dqa.local'],
            [
                'name' => 'J. Banda (Field Auditor)',
                'password' => bcrypt('password'),
                'roles' => [User::ROLE_AUDITOR],
                'is_active' => true,
            ]
        );

        // 3. Seed initial projects linked to Project Officers
        $this->call(ProjectSeeder::class);

        // 4. Seed health facilities and districts
        $this->call(SiteSeeder::class);

        // 5. Seed baseline audit data (AUD-001) linked to Auditor & Project Officer
        $this->call(AuditSeeder::class);
    }
}
