<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = [
            [
                'name' => 'Samalani Ana',
                'code' => 'SAMALANI-ANA',
                'description' => 'Samalani Ana Child Health and Community Nutrition Project',
                'is_active' => true,
            ],
            [
                'name' => 'Sample Project A',
                'code' => 'PRJ-A',
                'description' => 'Sample Primary Healthcare Project A',
                'is_active' => true,
            ],
            [
                'name' => 'Sample Project B',
                'code' => 'PRJ-B',
                'description' => 'Sample Community Health and Outreach Project B',
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
