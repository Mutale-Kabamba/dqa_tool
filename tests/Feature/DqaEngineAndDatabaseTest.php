<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\Project;
use App\Models\Setting;
use App\Services\DqaEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DqaEngineAndDatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_projects_seeded_successfully(): void
    {
        $samalani = Project::where('code', 'SAMALANI-ANA')->first();
        $this->assertNotNull($samalani);
        $this->assertEquals('Samalani Ana', $samalani->name);
        $this->assertTrue($samalani->is_active);

        $this->assertDatabaseHas('projects', ['code' => 'PRJ-A']);
        $this->assertDatabaseHas('projects', ['code' => 'PRJ-B']);
    }

    public function test_standard_rag_thresholds_seeded(): void
    {
        $setting = Setting::current();
        $this->assertNotNull($setting);
        $this->assertEquals(0.85, (float) $setting->green_threshold);
        $this->assertEquals(0.70, (float) $setting->yellow_threshold);
        $this->assertEquals(0.55, (float) $setting->orange_threshold);
    }

    public function test_baseline_audit_matches_excel_figures(): void
    {
        $audit = Audit::with('dimensions')->where('audit_code', 'AUD-001')->first();
        $this->assertNotNull($audit);
        $this->assertEquals('Lusaka District - Site 1', $audit->site_name);
        $this->assertEquals(250, $audit->overall_checked);
        $this->assertEquals(213, $audit->overall_compliant);
        $this->assertEquals('0.8520', (string) $audit->overall_score);
        $this->assertEquals('GREEN', $audit->overall_status);
        $this->assertEquals('Completeness, Timeliness', $audit->priority_areas);

        $dimensions = $audit->dimensions->keyBy('dimension_name');
        $this->assertCount(5, $dimensions);
        $this->assertEquals('GREEN', $dimensions['Accuracy']->status);
        $this->assertEquals('YELLOW', $dimensions['Completeness']->status);
        $this->assertEquals('GREEN', $dimensions['Consistency']->status);
        $this->assertEquals('ORANGE', $dimensions['Timeliness']->status);
        $this->assertEquals('GREEN', $dimensions['Validity']->status);
    }

    public function test_dqa_engine_service_computations(): void
    {
        $engine = new DqaEngineService();
        $this->assertEquals('GREEN', $engine->computeStatus(0.85));
        $this->assertEquals('YELLOW', $engine->computeStatus(0.70));
        $this->assertEquals('ORANGE', $engine->computeStatus(0.55));
        $this->assertEquals('RED', $engine->computeStatus(0.54));

        $result = $engine->computeAuditTotals([
            'Accuracy' => ['checked' => 10, 'compliant' => 10],
            'Completeness' => ['checked' => 10, 'compliant' => 6], // 60% -> Orange, flagged
        ]);

        $this->assertEquals(20, $result['overall_checked']);
        $this->assertEquals(16, $result['overall_compliant']);
        $this->assertEquals(0.80, $result['overall_score']);
        $this->assertEquals('YELLOW', $result['overall_status']);
        $this->assertEquals('Completeness', $result['priority_areas']);
    }
}
