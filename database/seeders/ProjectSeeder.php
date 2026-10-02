<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $officer1 = User::where('email', 'officer@dqa.local')->first();
        $peerOfficer = User::where('email', 'peer@dqa.local')->first();

        $projects = [
            [
                'name' => 'Samalani Ana',
                'code' => 'SAMALANI-ANA',
                'description' => 'Samalani Ana Child Health and Community Nutrition Project',
                'project_officer_id' => $officer1?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Sample Project A',
                'code' => 'PRJ-A',
                'description' => 'Sample Primary Healthcare Project A',
                'project_officer_id' => $peerOfficer?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Sample Project B',
                'code' => 'PRJ-B',
                'description' => 'Sample Community Health and Outreach Project B',
                'project_officer_id' => null,
                'is_active' => true,
            ],
        ];

        foreach ($projects as $project) {
            Project::updateOrCreate(
                ['code' => $project['code']],
                $project
            );
        }
    }
}
