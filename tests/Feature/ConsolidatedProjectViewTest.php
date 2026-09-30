<?php

namespace Tests\Feature;

use App\Filament\Pages\ConsolidatedProjectView;
use App\Models\Audit;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConsolidatedProjectViewTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::where('email', 'admin@dqa.local')->first();
        $this->project = Project::where('code', 'SAMALANI-ANA')->first();
    }

    public function test_admin_can_access_consolidated_project_view_page(): void
    {
        $response = $this->actingAs($this->user)->get('/admin/consolidated-project-view');
        $response->assertSuccessful();
        $response->assertSee('Consolidated Project Performance Review');
        $response->assertSee('Project & Multi-Tier Temporal Filter');
        $response->assertSee('Samalani Ana');
    }

    public function test_consolidated_project_view_renders_metrics(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ConsolidatedProjectView::class)
            ->set('project_id', $this->project->id)
            ->assertSee('SAMALANI-ANA')
            ->assertSee('Samalani Ana')
            ->assertSee('Verified Visits')
            ->assertSee('85.2%')
            ->assertSee('GREEN')
            ->assertSee('Project Dimensional Rollup')
            ->assertSee('Accuracy')
            ->assertSee('Completeness')
            ->assertSee('Consistency')
            ->assertSee('Timeliness')
            ->assertSee('Validity')
            ->assertSee('Recurring Priority Areas')
            ->assertSee('Site-by-Site Verification Breakdown')
            ->assertSee('Lusaka District - Site 1');
    }

    public function test_csv_export_returns_streamed_file(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ConsolidatedProjectView::class)
            ->set('project_id', $this->project->id)
            ->call('exportCsv');

        $response = $component->instance()->exportCsv();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('_DQA_Export_', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));
    }
}
