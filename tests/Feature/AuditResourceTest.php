<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::where('email', 'admin@dqa.local')->first();
    }

    public function test_admin_can_access_audit_list(): void
    {
        $response = $this->actingAs($this->user)->get('/admin/audits');
        $response->assertSuccessful();
        $response->assertSee('AUD-001');
        $response->assertSee('Lusaka District - Site 1');
    }

    public function test_admin_can_access_audit_create_page(): void
    {
        $response = $this->actingAs($this->user)->get('/admin/audits/create');
        $response->assertSuccessful();
        $response->assertSee('Section A: Audit Metadata');
        $response->assertSee('Section B: Five Dimension Entry Table');
        $response->assertSee('Section C: Automated Diagnostic Banner');
        $response->assertSee('Section D: Qualitative Feedback & Field Notes');
    }

    public function test_admin_can_access_audit_view_page(): void
    {
        $audit = Audit::where('audit_code', 'AUD-001')->first();
        $this->assertNotNull($audit);

        $response = $this->actingAs($this->user)->get("/admin/audits/{$audit->id}");
        $response->assertSuccessful();
        $response->assertSee('Audit Dossier Overview');
        $response->assertSee('AUD-001');
        $response->assertSee('Five Dimension Performance Scorecard');
    }

    public function test_admin_can_access_projects_list(): void
    {
        $response = $this->actingAs($this->user)->get('/admin/projects');
        $response->assertSuccessful();
        $response->assertSee('Samalani Ana');
        $response->assertSee('SAMALANI-ANA');
    }

    public function test_admin_can_access_settings_list(): void
    {
        $response = $this->actingAs($this->user)->get('/admin/settings');
        $response->assertSuccessful();
        $response->assertSee('85.0%');
        $response->assertSee('70.0%');
        $response->assertSee('55.0%');
    }

    public function test_can_store_audit_with_dimensions(): void
    {
        $project = \App\Models\Project::where('code', 'SAMALANI-ANA')->first();

        $audit = Audit::create([
            'audit_code' => 'AUD-TEST-99',
            'project_id' => $project->id,
            'site_name' => 'Chilanga Health Post',
            'auditor_name' => 'Field Auditor',
            'audit_date' => '2026-02-10',
            'period_month' => 2,
            'period_quarter' => 1,
            'period_year' => 2026,
            'period_label' => 'Feb-2026',
            'overall_checked' => 100,
            'overall_compliant' => 80,
            'overall_score' => 0.80,
            'overall_status' => 'YELLOW',
            'priority_areas' => 'Timeliness',
        ]);

        $audit->dimensions()->create([
            'dimension_name' => 'Timeliness',
            'checked_count' => 50,
            'compliant_count' => 30,
            'score_percentage' => 0.60,
            'status' => 'ORANGE',
        ]);

        $this->assertDatabaseHas('audits', ['audit_code' => 'AUD-TEST-99']);
        $this->assertDatabaseHas('audit_dimensions', ['audit_id' => $audit->id, 'dimension_name' => 'Timeliness']);
    }
}
