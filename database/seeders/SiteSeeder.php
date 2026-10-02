<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $samalani = Project::where('code', 'SAMALANI-ANA')->first();
        $projA = Project::where('code', 'PRJ-A')->first();
        $projB = Project::where('code', 'PRJ-B')->first();

        $sites = [
            [
                'name' => 'Chilenje Level 1 Hospital',
                'code' => 'SITE-LUS-001',
                'district' => 'Lusaka',
                'province' => 'Lusaka Province',
                'facility_type' => 'Level 1 District Hospital',
                'catchment_area' => 'Chilenje Ward - Approx. 65,000 population',
                'project_id' => $samalani?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Matero General Hospital',
                'code' => 'SITE-LUS-002',
                'district' => 'Lusaka',
                'province' => 'Lusaka Province',
                'facility_type' => 'Level 1 District Hospital',
                'catchment_area' => 'Matero Sub-district - 80,000 population',
                'project_id' => $samalani?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Livingstone Central Hospital',
                'code' => 'SITE-LIV-001',
                'district' => 'Livingstone',
                'province' => 'Southern Province',
                'facility_type' => 'Level 2 Provincial Hospital',
                'catchment_area' => 'Livingstone Urban & Tourist Zone - 120,000 population',
                'project_id' => $samalani?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Choma District Hospital',
                'code' => 'SITE-CHO-001',
                'district' => 'Choma',
                'province' => 'Southern Province',
                'facility_type' => 'Level 1 District Hospital',
                'catchment_area' => 'Choma Central - 55,000 population',
                'project_id' => $projA?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Ndola Teaching Hospital Urban Outpost',
                'code' => 'SITE-NDO-001',
                'district' => 'Ndola',
                'province' => 'Copperbelt Province',
                'facility_type' => 'Urban Health Centre',
                'catchment_area' => 'Ndola Central - 70,000 population',
                'project_id' => $projB?->id,
                'is_active' => true,
            ],
        ];

        foreach ($sites as $site) {
            Site::updateOrCreate(
                ['code' => $site['code']],
                $site
            );
        }
    }
}
