<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\AuditorQueueTableWidget;
use App\Filament\Widgets\AuditorStatsWidget;
use App\Filament\Widgets\DimensionPerformanceChartWidget;
use App\Filament\Widgets\DimensionScorecardWidget;
use App\Filament\Widgets\DqaOverviewStatsWidget;
use App\Filament\Widgets\MealAuditDispatchWidget;
use App\Filament\Widgets\ProjectCapaTrackerWidget;
use App\Filament\Widgets\ProjectComparativeTableWidget;
use App\Filament\Widgets\ProjectOfficerStatsWidget;
use App\Filament\Widgets\RoleContextBannerWidget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $mealOfficer;
    protected User $projectOfficer;
    protected User $peerReviewer;
    protected User $fieldAuditor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->mealOfficer = User::where('email', 'admin@dqa.local')->first();
        $this->projectOfficer = User::where('email', 'officer@dqa.local')->first();
        $this->peerReviewer = User::where('email', 'peer@dqa.local')->first();
        $this->fieldAuditor = User::where('email', 'auditor@dqa.local')->first();
    }

    public function test_meal_officer_dashboard_matches_role(): void
    {
        $response = $this->actingAs($this->mealOfficer)->get('/admin');
        $response->assertSuccessful();
        $response->assertSee('MEAL Global Quality Oversight Cockpit');

        $this->actingAs($this->mealOfficer);
        Livewire::test(RoleContextBannerWidget::class)
            ->assertSee('MEAL Global Quality Oversight Cockpit')
            ->assertSee('Global Scope');

        Livewire::test(MealAuditDispatchWidget::class)
            ->assertSee('Central Dispatcher');
    }

    public function test_project_officer_dashboard_matches_role(): void
    {
        $response = $this->actingAs($this->projectOfficer)->get('/admin');
        $response->assertSuccessful();
        $response->assertSee('Project Officer Quality Cockpit');

        $this->actingAs($this->projectOfficer);
        Livewire::test(RoleContextBannerWidget::class)
            ->assertSee('Project Officer Quality Cockpit')
            ->assertSee('Samalani Ana');

        Livewire::test(ProjectCapaTrackerWidget::class)
            ->assertSee('Project CAPA Remediation and Action Items');
    }

    public function test_field_auditor_dashboard_matches_role(): void
    {
        $response = $this->actingAs($this->fieldAuditor)->get('/admin');
        $response->assertSuccessful();
        $response->assertSee('Field and Peer Auditor Workspace');

        $this->actingAs($this->fieldAuditor);
        Livewire::test(RoleContextBannerWidget::class)
            ->assertSee('Field and Peer Auditor Workspace')
            ->assertSee('Assigned Audit Queue');

        Livewire::test(AuditorQueueTableWidget::class)
            ->assertSee('My Assigned Audit Tasks and Verification Queue');
    }

    public function test_peer_auditor_dual_role_dashboard_matches_role(): void
    {
        $response = $this->actingAs($this->peerReviewer)->get('/admin');
        $response->assertSuccessful();
        $response->assertSee('Project Quality and Peer Reviewer Cockpit');

        $this->actingAs($this->peerReviewer);
        Livewire::test(RoleContextBannerWidget::class)
            ->assertSee('Project Quality and Peer Reviewer Cockpit')
            ->assertSee('Dual-Role Active')
            ->assertSee('Anti-Self-Audit Enforced');

        Livewire::test(AuditorQueueTableWidget::class)
            ->assertSee('My Assigned Audit Tasks and Verification Queue');
    }

    public function test_dashboard_filters_default_to_all_projects(): void
    {
        $this->actingAs($this->mealOfficer);

        Livewire::test(Dashboard::class)
            ->assertSet('filters.project_id', null)
            ->assertSet('filters.period_year', null)
            ->assertSet('filters.period_quarter', null)
            ->assertSet('filters.period_month', null)
            ->assertSee('All Projects');
    }

    public function test_auditor_dashboard_filters_by_assigned_and_done_not_by_project(): void
    {
        $this->actingAs($this->fieldAuditor);

        Livewire::test(Dashboard::class)
            ->assertSet('filters.execution_status', null)
            ->assertSet('filters.period_year', null)
            ->assertSet('filters.period_quarter', null)
            ->assertSet('filters.period_month', null)
            ->assertSee('Audit Status')
            ->assertSee('All Assigned & Done')
            ->assertDontSee('All Projects');
    }

    public function test_dashboard_kpi_stats_widget_for_meal_officer(): void
    {
        $this->actingAs($this->mealOfficer);

        Livewire::test(DqaOverviewStatsWidget::class)
            ->assertSee('Total Audits Conducted')
            ->assertSee('Cumulative Records Audited')
            ->assertSee('50')
            ->assertSee('Portfolio Quality Index')
            ->assertSee('85.2%')
            ->assertSee('Pending Dispatch')
            ->assertSee('Active CAPA Actions');
    }

    public function test_project_officer_stats_widget(): void
    {
        $this->actingAs($this->projectOfficer);

        Livewire::test(ProjectOfficerStatsWidget::class)
            ->assertSee('Project Audit Status')
            ->assertSee('Audited Sites & Facilities')
            ->assertSee('Open CAPA Remediation Items')
            ->assertSee('Submission On-Time Rate');
    }

    public function test_auditor_stats_widget(): void
    {
        $this->actingAs($this->fieldAuditor);

        Livewire::test(AuditorStatsWidget::class)
            ->assertSee('Audits Awaiting Execution')
            ->assertSee('Audits Completed This Month')
            ->assertSee('Urgent Audits');
    }

    public function test_dashboard_five_dimensions_scorecard_widget(): void
    {
        $this->actingAs($this->mealOfficer);

        Livewire::test(DimensionScorecardWidget::class)
            ->assertSee('5-Dimension Quality Scorecard')
            ->assertSee('Accuracy')
            ->assertSee('Completeness')
            ->assertSee('Consistency')
            ->assertSee('Timeliness')
            ->assertSee('Validity')
            ->assertSee('85.2%');
    }

    public function test_dashboard_dimension_performance_chart_widget(): void
    {
        $this->actingAs($this->mealOfficer);

        Livewire::test(DimensionPerformanceChartWidget::class)
            ->assertSee('Dimensional Compliance vs 85% Benchmark');
    }

    public function test_dashboard_project_comparative_table_widget(): void
    {
        $this->actingAs($this->mealOfficer);

        Livewire::test(ProjectComparativeTableWidget::class)
            ->assertSee('Programmatic Performance Summary')
            ->assertSee('Samalani Ana')
            ->assertSee('SAMALANI-ANA');
    }
}
