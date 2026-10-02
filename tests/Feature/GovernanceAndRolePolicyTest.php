<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\Project;
use App\Models\User;
use App\Policies\AuditPolicy;
use App\Policies\ProjectPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GovernanceAndRolePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $mealOfficer;
    protected User $projectOfficer;
    protected User $peerReviewer;
    protected User $fieldAuditor;
    protected Project $samalaniProject;
    protected Project $sampleProjectA;
    protected Project $sampleProjectB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->mealOfficer = User::where('email', 'admin@dqa.local')->first();
        $this->projectOfficer = User::where('email', 'officer@dqa.local')->first();
        $this->peerReviewer = User::where('email', 'peer@dqa.local')->first();
        $this->fieldAuditor = User::where('email', 'auditor@dqa.local')->first();

        $this->samalaniProject = Project::where('code', 'SAMALANI-ANA')->first();
        $this->sampleProjectA = Project::where('code', 'PRJ-A')->first();
        $this->sampleProjectB = Project::where('code', 'PRJ-B')->first();
    }

    public function test_meal_officer_has_full_administrative_access(): void
    {
        $this->assertTrue($this->mealOfficer->isMealOfficer());

        $this->actingAs($this->mealOfficer)->get('/admin/users')->assertSuccessful()->assertSee('User & Role Management');
        $this->actingAs($this->mealOfficer)->get('/admin/settings')->assertSuccessful()->assertSee('RAG Thresholds');
        $this->actingAs($this->mealOfficer)->get('/admin/projects')->assertSuccessful();
        $this->actingAs($this->mealOfficer)->get('/admin/audits')->assertSuccessful();
    }

    public function test_meal_officer_can_see_all_projects_in_project_list(): void
    {
        $response = $this->actingAs($this->mealOfficer)->get('/admin/projects');
        $response->assertSuccessful();
        $response->assertSee('Samalani Ana');
        $response->assertSee('Sample Project A');
        $response->assertSee('Sample Project B');
    }

    public function test_project_officer_can_only_see_their_own_assigned_projects(): void
    {
        // J. Mwila is Project Officer for Samalani Ana only.
        $this->assertEquals($this->projectOfficer->id, $this->samalaniProject->project_officer_id);

        $response = $this->actingAs($this->projectOfficer)->get('/admin/projects');
        $response->assertSuccessful();
        $response->assertSee('Samalani Ana');
        $response->assertDontSee('Sample Project A');
        $response->assertDontSee('Sample Project B');
    }

    public function test_project_policy_restricts_viewing_to_assigned_project_officer(): void
    {
        $policy = new ProjectPolicy();

        // Project Officer can view their assigned project
        $this->assertTrue($policy->view($this->projectOfficer, $this->samalaniProject));

        // Project Officer cannot view another project they do not manage
        $this->assertFalse($policy->view($this->projectOfficer, $this->sampleProjectA));
        $this->assertFalse($policy->view($this->projectOfficer, $this->sampleProjectB));

        // MEAL Officer can view all projects
        $this->assertTrue($policy->view($this->mealOfficer, $this->samalaniProject));
        $this->assertTrue($policy->view($this->mealOfficer, $this->sampleProjectA));
    }

    public function test_project_officer_cannot_access_user_management_or_rag_settings(): void
    {
        $this->assertTrue($this->projectOfficer->isProjectOfficer());
        $this->assertFalse($this->projectOfficer->isMealOfficer());

        $this->actingAs($this->projectOfficer)->get('/admin/users')->assertForbidden();
        $this->actingAs($this->projectOfficer)->get('/admin/settings')->assertForbidden();
        $this->actingAs($this->projectOfficer)->get('/admin/projects')->assertSuccessful();
        $this->actingAs($this->projectOfficer)->get('/admin/audit-submissions')->assertSuccessful();
        $this->actingAs($this->projectOfficer)->get('/admin/capa-managements')->assertSuccessful();
        $this->actingAs($this->projectOfficer)->get('/admin/audits')->assertForbidden();
    }

    public function test_auditor_cannot_access_user_management_or_rag_settings(): void
    {
        $this->assertTrue($this->fieldAuditor->isAuditor());
        $this->assertFalse($this->fieldAuditor->isMealOfficer());

        $this->actingAs($this->fieldAuditor)->get('/admin/users')->assertForbidden();
        $this->actingAs($this->fieldAuditor)->get('/admin/settings')->assertForbidden();
        $this->actingAs($this->fieldAuditor)->get('/admin/audits')->assertSuccessful();
    }

    public function test_peer_auditor_holds_dual_roles_simultaneously(): void
    {
        $this->assertTrue($this->peerReviewer->isProjectOfficer());
        $this->assertTrue($this->peerReviewer->isAuditor());
        $this->assertFalse($this->peerReviewer->isMealOfficer());
    }

    public function test_project_officer_is_linked_to_managed_project(): void
    {
        $this->assertEquals($this->projectOfficer->id, $this->samalaniProject->project_officer_id);
        $this->assertEquals($this->projectOfficer->id, $this->samalaniProject->projectOfficer->id);

        $this->assertEquals($this->peerReviewer->id, $this->sampleProjectA->project_officer_id);
        $this->assertEquals($this->peerReviewer->id, $this->sampleProjectA->projectOfficer->id);
    }

    public function test_anti_self_audit_policy_allows_peer_reviews_on_other_projects(): void
    {
        // K. Tembo is Project Officer for Sample Project A.
        // As a peer reviewer, K. Tembo can audit Samalani Ana (Project Officer: J. Mwila).
        $audit = Audit::create([
            'audit_code' => 'AUD-PEER-001',
            'project_id' => $this->samalaniProject->id,
            'auditor_id' => $this->peerReviewer->id,
            'project_officer_id' => $this->samalaniProject->project_officer_id,
            'site_name' => 'Kafue District Site 3',
            'auditor_name' => $this->peerReviewer->name,
            'audit_date' => '2026-02-15',
            'period_month' => 2,
            'period_quarter' => 1,
            'period_year' => 2026,
            'period_label' => 'Feb-2026',
            'overall_checked' => 50,
            'overall_compliant' => 45,
            'overall_score' => 0.90,
            'overall_status' => 'GREEN',
        ]);

        $this->assertDatabaseHas('audits', [
            'audit_code' => 'AUD-PEER-001',
            'auditor_id' => $this->peerReviewer->id,
            'project_officer_id' => $this->projectOfficer->id,
        ]);

        $this->assertNotEquals($audit->auditor_id, $audit->project_officer_id);
    }

    public function test_anti_self_audit_policy_blocks_self_audit_assignment(): void
    {
        // Anti-Self-Audit Policy rule: K. Tembo cannot audit Sample Project A where K. Tembo is the designated Project Officer
        $this->assertEquals($this->sampleProjectA->project_officer_id, $this->peerReviewer->id);

        $policy = new AuditPolicy();

        $selfAudit = new Audit([
            'project_id' => $this->sampleProjectA->id,
            'project_officer_id' => $this->peerReviewer->id,
            'auditor_id' => $this->peerReviewer->id,
        ]);
        $selfAudit->setRelation('project', $this->sampleProjectA);

        // Update capability as an Auditor on their own project must be rejected
        $this->assertFalse($policy->update($this->peerReviewer, $selfAudit));
    }

    public function test_auditor_cannot_create_arbitrary_audits(): void
    {
        $policy = new AuditPolicy();
        $this->assertFalse($policy->create($this->fieldAuditor));

        // Auditors cannot access create page
        $this->actingAs($this->fieldAuditor)->get('/admin/audits/create')->assertForbidden();

        // Auditors only work on assigned tasks; header action buttons are hidden
        $response = $this->actingAs($this->fieldAuditor)->get('/admin/audits');
        $response->assertSuccessful();
        $response->assertDontSee('+ New Audit Visit');
        $response->assertDontSee('Import Batch Audits');
        $response->assertDontSee('CSV Template');
    }
}
