<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\AuditActionItem;
use App\Models\AuditDimension;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;
use App\Services\DqaEngineService;
use App\Services\DqaImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DqaImprovementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_engine_clamps_compliant_counts_and_recalculates_audits(): void
    {
        $engine = new DqaEngineService();

        // Check calculation with compliant > checked
        $dimensionsData = [
            'Accuracy' => ['checked' => 40, 'compliant' => 50], // Should clamp to 40
        ];

        $totals = $engine->computeAuditTotals($dimensionsData);
        $this->assertEquals(40, $totals['overall_checked']);
        $this->assertEquals(40, $totals['overall_compliant']);
        $this->assertEquals(1.0, $totals['overall_score']);
        $this->assertEquals('GREEN', $totals['overall_status']);

        // Test batch recalculation
        $audit = Audit::first();
        $this->assertNotNull($audit);

        $count = $engine->recalculateAllAudits();
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function test_recalculate_artisan_command_works(): void
    {
        $this->artisan('dqa:recalculate')
            ->expectsOutputToContain('Successfully recalculated')
            ->assertExitCode(0);
    }

    public function test_audit_action_item_capa_lifecycle_and_relationships(): void
    {
        $audit = Audit::first();
        $this->assertNotNull($audit);

        $actionItem = AuditActionItem::create([
            'audit_id' => $audit->id,
            'dimension_name' => 'Timeliness',
            'issue_description' => 'Delayed registers by 14 days',
            'root_cause_category' => 'Staffing Shortage',
            'action_plan' => 'Deploy relief data officer by end of month',
            'responsible_person' => 'Sister Grace',
            'due_date' => now()->addDays(10),
            'status' => 'OPEN',
        ]);

        $this->assertDatabaseHas('audit_action_items', [
            'id' => $actionItem->id,
            'status' => 'OPEN',
            'root_cause_category' => 'Staffing Shortage',
        ]);

        $this->assertEquals($audit->id, $actionItem->audit->id);
        $this->assertTrue($audit->actionItems->contains($actionItem));
        $this->assertEquals('OPEN', $actionItem->effective_status);

        // Test overdue status calculation
        $overdueItem = AuditActionItem::create([
            'audit_id' => $audit->id,
            'dimension_name' => 'Accuracy',
            'issue_description' => 'Unverified patient cards',
            'root_cause_category' => 'Training & Mentorship Need',
            'action_plan' => 'Conduct on-site mentoring session',
            'responsible_person' => 'Lead Auditor',
            'due_date' => now()->subDays(5),
            'status' => 'IN_PROGRESS',
        ]);

        $this->assertEquals('OVERDUE', $overdueItem->effective_status);
    }

    public function test_csv_template_generation_and_batch_import(): void
    {
        $importService = app(DqaImportService::class);
        $csvTemplate = $importService->generateCsvTemplate();

        $this->assertStringContainsString('Audit Code', $csvTemplate);
        $this->assertStringContainsString('SAMALANI-ANA', $csvTemplate);
        $this->assertStringContainsString('Chilenje First Level Hospital', $csvTemplate);

        // Create temporary CSV file for import testing
        $tempFile = tempnam(sys_get_temp_dir(), 'dqa_test_') . '.csv';
        file_put_contents($tempFile, $csvTemplate);

        $result = $importService->importCsv($tempFile);
        $this->assertEquals(1, $result['imported']);
        $this->assertEmpty($result['errors']);

        $this->assertDatabaseHas('audits', [
            'audit_code' => 'AUD-101',
            'site_name' => 'Chilenje First Level Hospital',
        ]);

        $this->assertDatabaseHas('audit_dimensions', [
            'dimension_name' => 'Timeliness',
            'checked_count' => 50,
            'compliant_count' => 38,
        ]);

        unlink($tempFile);
    }

    public function test_import_artisan_command_works(): void
    {
        $importService = app(DqaImportService::class);
        $csvTemplate = $importService->generateCsvTemplate();

        $tempFile = tempnam(sys_get_temp_dir(), 'dqa_cmd_') . '.csv';
        file_put_contents($tempFile, $csvTemplate);

        $this->artisan('dqa:import', ['file' => $tempFile])
            ->expectsOutputToContain('Successfully imported/updated')
            ->assertExitCode(0);

        unlink($tempFile);
    }

    public function test_consolidated_view_computes_trajectories_and_capa(): void
    {
        $project = Project::first();
        $this->assertNotNull($project);

        // Add a second audit for the same site in a different period to test longitudinal trajectory
        $engine = new DqaEngineService();
        $calc = $engine->computeAuditTotals([
            'Accuracy' => ['checked' => 50, 'compliant' => 49],
            'Completeness' => ['checked' => 50, 'compliant' => 48],
            'Consistency' => ['checked' => 50, 'compliant' => 49],
            'Timeliness' => ['checked' => 50, 'compliant' => 45],
            'Validity' => ['checked' => 50, 'compliant' => 48],
        ]);

        $secondAudit = Audit::create([
            'audit_code' => 'AUD-002',
            'project_id' => $project->id,
            'site_name' => 'Lusaka District - Site 1',
            'auditor_name' => 'J. Banda',
            'audit_date' => '2026-04-15',
            'period_month' => 4,
            'period_quarter' => 2,
            'period_year' => 2026,
            'period_label' => 'Apr-2026',
            'overall_checked' => $calc['overall_checked'],
            'overall_compliant' => $calc['overall_compliant'],
            'overall_score' => $calc['overall_score'],
            'overall_status' => $calc['overall_status'],
            'priority_areas' => $calc['priority_areas'],
        ]);

        foreach ($calc['dimensions'] as $dim) {
            AuditDimension::create([
                'audit_id' => $secondAudit->id,
                'dimension_name' => $dim['dimension_name'],
                'checked_count' => $dim['checked_count'],
                'compliant_count' => $dim['compliant_count'],
                'score_percentage' => $dim['score_percentage'],
                'status' => $dim['status'],
            ]);
        }

        $user = User::factory()->create(['roles' => [User::ROLE_MEAL_OFFICER]]);
        $response = $this->actingAs($user)->get(route('filament.admin.pages.consolidated-project-view'));
        $response->assertSuccessful();
        $response->assertSee('Lusaka District - Site 1');
        $response->assertSee('Facility Longitudinal Quality Trajectory');
    }

    public function test_capa_tasks_renders_top_kpi_cards_widget(): void
    {
        $user = User::factory()->create(['roles' => [User::ROLE_MEAL_OFFICER]]);
        $this->actingAs($user);

        \Livewire\Livewire::test(\App\Filament\Widgets\CapaOverviewStatsWidget::class)
            ->assertSee('Total Actions')
            ->assertSee('Resolved')
            ->assertSee('In Progress')
            ->assertSee('Open / Overdue');
    }
}

