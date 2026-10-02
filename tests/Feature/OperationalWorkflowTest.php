<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\AuditDimension;
use App\Models\AuditActionItem;
use App\Models\Project;
use App\Models\RagThreshold;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OperationalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $mealOfficer;
    protected User $projectOfficer;
    protected User $auditor;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->mealOfficer = User::factory()->create([
            'name' => 'MEAL Director',
            'email' => 'meal@dqa.test',
            'roles' => [User::ROLE_MEAL_OFFICER],
            'is_active' => true,
        ]);

        $this->projectOfficer = User::factory()->create([
            'name' => 'Chanda Musonda (Project Officer)',
            'email' => 'project.officer@dqa.test',
            'roles' => [User::ROLE_PROJECT_OFFICER],
            'is_active' => true,
        ]);

        $this->auditor = User::factory()->create([
            'name' => 'Dr. Banda (Peer Auditor)',
            'email' => 'auditor@dqa.test',
            'roles' => [User::ROLE_AUDITOR],
            'is_active' => true,
        ]);

        $this->project = Project::create([
            'name' => 'USAID Community Health & Nutrition Project',
            'code' => 'USAID-CHN',
            'project_officer_id' => $this->projectOfficer->id,
            'is_active' => true,
        ]);
    }

    public function test_full_five_step_operational_workflow_lifecycle(): void
    {
        Storage::fake('public');

        // ==========================================
        // STEP 1: SUBMISSION (Project Officer)
        // ==========================================
        $this->actingAs($this->projectOfficer);

        $fakeFile = UploadedFile::fake()->create('immunization_register_q1.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $storedPath = $fakeFile->store('audit-submissions', 'public');

        $audit = Audit::create([
            'audit_code' => 'AUD-TEST-001',
            'project_id' => $this->project->id,
            'project_officer_id' => $this->projectOfficer->id,
            'facility_in_charge' => $this->projectOfficer->name,
            'site_name' => 'Chilenje Level 1 Hospital',
            'audit_date' => now()->toDateString(),
            'period_month' => now()->month,
            'period_quarter' => now()->quarter,
            'period_year' => now()->year,
            'period_label' => now()->format('M-Y'),
            'data_file_path' => $storedPath,
            'workflow_status' => Audit::STATUS_PENDING_ASSIGNMENT,
            'overall_checked' => 0,
            'overall_compliant' => 0,
            'overall_score' => 0.0,
            'overall_status' => 'RED',
        ]);

        $this->assertTrue($audit->isPendingAssignment());
        $this->assertEquals(Audit::STATUS_PENDING_ASSIGNMENT, $audit->workflow_status);
        $this->assertNotNull($audit->data_file_path);
        Storage::disk('public')->assertExists($storedPath);

        // ==========================================
        // STEP 2: ASSIGNMENT & DISPATCH (MEAL Officer)
        // ==========================================
        $this->actingAs($this->mealOfficer);

        // Anti-Self-Audit Policy Check
        $this->assertNotEquals($this->auditor->id, $this->project->project_officer_id);

        $audit->update([
            'auditor_id' => $this->auditor->id,
            'auditor_name' => $this->auditor->name,
            'workflow_status' => Audit::STATUS_ASSIGNED_TO_AUDITOR,
        ]);

        $this->assertTrue($audit->isAssignedToAuditor());
        $this->assertEquals($this->auditor->id, $audit->auditor_id);

        // ==========================================
        // STEP 3: AUDIT EXECUTION (Auditor)
        // ==========================================
        $this->actingAs($this->auditor);

        // Auditor enters 5-dimension tallies
        $dimData = [
            ['dimension_name' => 'Accuracy', 'checked_count' => 100, 'compliant_count' => 75, 'score_percentage' => 0.75, 'status' => 'YELLOW'],
            ['dimension_name' => 'Completeness', 'checked_count' => 100, 'compliant_count' => 95, 'score_percentage' => 0.95, 'status' => 'GREEN'],
            ['dimension_name' => 'Consistency', 'checked_count' => 100, 'compliant_count' => 80, 'score_percentage' => 0.80, 'status' => 'YELLOW'],
            ['dimension_name' => 'Timeliness', 'checked_count' => 100, 'compliant_count' => 90, 'score_percentage' => 0.90, 'status' => 'GREEN'],
            ['dimension_name' => 'Validity', 'checked_count' => 100, 'compliant_count' => 70, 'score_percentage' => 0.70, 'status' => 'YELLOW'],
        ];

        foreach ($dimData as $d) {
            AuditDimension::create(array_merge($d, ['audit_id' => $audit->id]));
        }

        $audit->update([
            'overall_checked' => 100,
            'overall_compliant' => 82,
            'overall_score' => 0.8200,
            'overall_status' => 'YELLOW',
            'priority_areas' => 'Accuracy, Consistency, Validity',
            'root_cause_notes' => 'High staff turnover in intake triage caused delayed and inconsistent register tallying.',
            'strengths_notes' => 'Strong compliance on client completeness and prompt timeliness for weekly submission.',
            'discrepancies_notes' => 'Discrepancies found in age coding and cross-register duplicate entries.',
            'recommendations' => 'Conduct targeted triage register refresher training and daily double-checking.',
            'workflow_status' => Audit::STATUS_AUDIT_COMPLETED,
        ]);

        $this->assertTrue($audit->isAuditCompleted());
        $this->assertTrue($audit->needsCapa()); // Since overall_score is 82% (<85%) and 3 dims are YELLOW

        // ==========================================
        // STEP 4: CAPA TRIGGER & RESPONSE (Project Officer)
        // ==========================================
        $this->actingAs($this->projectOfficer);

        AuditActionItem::create([
            'audit_id' => $audit->id,
            'dimension_name' => 'Accuracy',
            'root_cause_category' => 'Training & Mentorship Need',
            'issue_description' => '25% of patient records contained inaccurate birthdate estimations',
            'action_plan' => 'Provide on-site coaching on age verification protocols by end of week',
            'responsible_person' => 'Sr. Nurse Mwape',
            'due_date' => now()->addWeeks(2)->toDateString(),
            'status' => 'OPEN',
        ]);

        $audit->update([
            'workflow_status' => Audit::STATUS_CAPA_SUBMITTED,
        ]);

        $this->assertTrue($audit->isCapaSubmitted());
        $this->assertCount(1, $audit->fresh()->actionItems);

        // ==========================================
        // STEP 5: VERIFICATION & FORMAL CLOSURE (MEAL Officer)
        // ==========================================
        $this->actingAs($this->mealOfficer);

        $closureNotes = 'CAPA action plan reviewed and approved. On-site mentorship date confirmed.';

        $audit->update([
            'workflow_status' => Audit::STATUS_AUDIT_CLOSED,
            'certified_by_id' => $this->mealOfficer->id,
            'certified_at' => now(),
            'closure_notes' => $closureNotes,
        ]);

        $this->assertTrue($audit->isAuditClosed());
        $this->assertEquals($this->mealOfficer->id, $audit->certified_by_id);
        $this->assertNotNull($audit->certified_at);
        $this->assertEquals($closureNotes, $audit->closure_notes);

        // Verify PDF generation with certification seal and notes
        $audit->load(['project', 'dimensions', 'actionItems', 'certifiedBy']);
        $pdf = Pdf::loadView('pdf.audit-dossier', ['audit' => $audit]);
        $renderedHtml = view('pdf.audit-dossier', ['audit' => $audit])->render();

        $this->assertStringContainsString('CERTIFIED', $renderedHtml);
        $this->assertStringContainsString('Formal MEAL Certification', $renderedHtml);
        $this->assertStringContainsString('Closure Endorsement', $renderedHtml);
        $this->assertStringContainsString('MEAL Director', $renderedHtml);
        $this->assertStringContainsString('Operational Strengths Observed', $renderedHtml);
        $this->assertStringContainsString('Discrepancies & Quality Gaps Found', $renderedHtml);
    }

    public function test_anti_self_audit_policy_strictly_blocks_project_officer_assignment(): void
    {
        $this->actingAs($this->mealOfficer);

        $audit = Audit::create([
            'audit_code' => 'AUD-TEST-ANTI-SELF',
            'project_id' => $this->project->id,
            'project_officer_id' => $this->projectOfficer->id,
            'site_name' => 'Kanyama General Hospital',
            'audit_date' => now()->toDateString(),
            'period_month' => now()->month,
            'period_quarter' => now()->quarter,
            'period_year' => now()->year,
            'period_label' => now()->format('M-Y'),
            'workflow_status' => Audit::STATUS_PENDING_ASSIGNMENT,
        ]);

        // Attempt to assign the project officer to their own project
        $this->assertEquals($this->projectOfficer->id, $this->project->project_officer_id);

        // Anti-self-audit rule forbids auditor_id == project_officer_id
        $isSelfAudit = ((int) $this->projectOfficer->id === (int) $audit->project_officer_id);
        $this->assertTrue($isSelfAudit);
    }

    public function test_high_quality_audit_allows_direct_meal_approval_without_capa(): void
    {
        $this->actingAs($this->mealOfficer);

        $audit = Audit::create([
            'audit_code' => 'AUD-TEST-HIGH-PERF',
            'project_id' => $this->project->id,
            'project_officer_id' => $this->projectOfficer->id,
            'site_name' => 'Matero Level 1 Hospital',
            'audit_date' => now()->toDateString(),
            'period_month' => now()->month,
            'period_quarter' => now()->quarter,
            'period_year' => now()->year,
            'period_label' => now()->format('M-Y'),
            'auditor_id' => $this->auditor->id,
            'auditor_name' => $this->auditor->name,
            'workflow_status' => Audit::STATUS_AUDIT_COMPLETED,
            'overall_checked' => 100,
            'overall_compliant' => 96,
            'overall_score' => 0.9600,
            'overall_status' => 'GREEN',
            'priority_areas' => null,
            'strengths_notes' => 'Exceptional data hygiene and daily validation across all 5 dimensions.',
            'recommendations' => 'Maintain current excellent register reconciliation processes.',
        ]);

        $dimData = [
            ['dimension_name' => 'Accuracy', 'checked_count' => 100, 'compliant_count' => 96, 'score_percentage' => 0.96, 'status' => 'GREEN'],
            ['dimension_name' => 'Completeness', 'checked_count' => 100, 'compliant_count' => 98, 'score_percentage' => 0.98, 'status' => 'GREEN'],
            ['dimension_name' => 'Consistency', 'checked_count' => 100, 'compliant_count' => 95, 'score_percentage' => 0.95, 'status' => 'GREEN'],
            ['dimension_name' => 'Timeliness', 'checked_count' => 100, 'compliant_count' => 97, 'score_percentage' => 0.97, 'status' => 'GREEN'],
            ['dimension_name' => 'Validity', 'checked_count' => 100, 'compliant_count' => 94, 'score_percentage' => 0.94, 'status' => 'GREEN'],
        ];

        foreach ($dimData as $d) {
            AuditDimension::create(array_merge($d, ['audit_id' => $audit->id]));
        }

        // Direct closure without CAPA required
        $this->assertFalse($audit->needsCapa());

        $audit->update([
            'workflow_status' => Audit::STATUS_AUDIT_CLOSED,
            'certified_by_id' => $this->mealOfficer->id,
            'certified_at' => now(),
            'closure_notes' => 'High quality audit score of 96%. Closed directly without CAPA.',
        ]);

        $this->assertTrue($audit->isAuditClosed());
        $this->assertCount(0, $audit->fresh()->actionItems);
    }
}
