<?php

namespace Database\Seeders;

use App\Models\Audit;
use App\Models\AuditDimension;
use App\Models\Project;
use App\Services\DqaEngineService;
use Illuminate\Database\Seeder;

class AuditSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $project = Project::where('code', 'SAMALANI-ANA')->first();

        if (! $project) {
            return;
        }

        $engine = new DqaEngineService();

        $dimensionsData = [
            'Accuracy' => ['checked' => 50, 'compliant' => 46],
            'Completeness' => ['checked' => 50, 'compliant' => 41],
            'Consistency' => ['checked' => 50, 'compliant' => 48],
            'Timeliness' => ['checked' => 50, 'compliant' => 33],
            'Validity' => ['checked' => 50, 'compliant' => 45],
        ];

        $calculated = $engine->computeAuditTotals($dimensionsData);

        $audit = Audit::updateOrCreate(
            ['audit_code' => 'AUD-001'],
            [
                'project_id' => $project->id,
                'site_name' => 'Lusaka District - Site 1',
                'auditor_name' => 'J. Banda',
                'audit_date' => '2026-01-15',
                'period_month' => 1,
                'period_quarter' => 1,
                'period_year' => 2026,
                'period_label' => 'Jan-2026',
                'overall_checked' => $calculated['overall_checked'],
                'overall_compliant' => $calculated['overall_compliant'],
                'overall_score' => $calculated['overall_score'],
                'overall_status' => $calculated['overall_status'],
                'priority_areas' => $calculated['priority_areas'],
                'root_cause_notes' => 'Severe staffing shortage during January immunization campaign led to delayed register updates.',
                'recommendations' => 'Facility in-charge agreed to assign a dedicated intake officer by February 15.',
                'facility_in_charge' => 'Sister M. Phiri',
            ]
        );

        foreach ($calculated['dimensions'] as $dim) {
            AuditDimension::updateOrCreate(
                [
                    'audit_id' => $audit->id,
                    'dimension_name' => $dim['dimension_name'],
                ],
                [
                    'checked_count' => $dim['checked_count'],
                    'compliant_count' => $dim['compliant_count'],
                    'score_percentage' => $dim['score_percentage'],
                    'status' => $dim['status'],
                ]
            );
        }
    }
}
