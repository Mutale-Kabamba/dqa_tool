<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::updateOrCreate(
            ['id' => 1],
            [
                'green_threshold' => 0.85,
                'yellow_threshold' => 0.70,
                'orange_threshold' => 0.55,
            ]
        );
    }
}
