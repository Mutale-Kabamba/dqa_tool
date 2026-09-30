<?php

namespace Tests\Feature;

use App\Filament\Widgets\DimensionPerformanceChartWidget;
use App\Filament\Widgets\DimensionScorecardWidget;
use App\Filament\Widgets\DqaOverviewStatsWidget;
use App\Filament\Widgets\ProjectComparativeTableWidget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::where('email', 'admin@dqa.local')->first();
    }

    public function test_admin_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->user)->get('/admin');
        $response->assertSuccessful();
        $response->assertSee('Executive DQA Cockpit');
        $response->assertSee('Multi-Tier Filters');
        $response->assertSee('All Projects');
        $response->assertSee('All Years');
        $response->assertSee('All Quarters');
        $response->assertSee('All Months');
    }

    public function test_dashboard_filters_default_to_all_projects(): void
    {
        $this->actingAs($this->user);

        Livewire::test(\App\Filament\Pages\Dashboard::class)
            ->assertSet('filters.project_id', null)
            ->assertSet('filters.period_year', null)
            ->assertSet('filters.period_quarter', null)
            ->assertSet('filters.period_month', null)
            ->assertSee('All Projects');
    }

    public function test_dashboard_kpi_stats_widget(): void
    {
        $this->actingAs($this->user);

        Livewire::test(DqaOverviewStatsWidget::class)
            ->assertSee('Total Audits Completed')
            ->assertSee('Cumulative Records Audited')
            ->assertSee('250')
            ->assertSee('213 records found compliant')
            ->assertSee('Overall Portfolio Health')
            ->assertSee('85.2%')
            ->assertSee('Status: GREEN')
            ->assertSee('Critical Sites for Follow-up')
            ->assertSee('Facilities flagged Orange or Red (< 70%)')
            ->assertSee('1');
    }

    public function test_dashboard_five_dimensions_scorecard_widget(): void
    {
        $this->actingAs($this->user);

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
        $this->actingAs($this->user);

        Livewire::test(DimensionPerformanceChartWidget::class)
            ->assertSee('Dimensional Compliance vs 85% Benchmark');
    }

    public function test_dashboard_project_comparative_table_widget(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ProjectComparativeTableWidget::class)
            ->assertSee('Programmatic Performance Summary')
            ->assertSee('Samalani Ana')
            ->assertSee('SAMALANI-ANA');
    }
}
