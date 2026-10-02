<?php

namespace App\Filament\Pages;

use App\Models\Audit;
use App\Models\Project;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Dashboard';

    public function getTitle(): string
    {
        $user = auth()->user();
        if ($user?->isMealOfficer()) {
            return 'MEAL Global Quality Oversight Cockpit';
        }
        if ($user?->isProjectOfficer() && $user?->isAuditor()) {
            return 'Project Quality and Peer Reviewer Cockpit';
        }
        if ($user?->isProjectOfficer()) {
            return 'Project Officer Quality Cockpit';
        }
        if ($user?->isAuditor()) {
            return 'Field and Peer Auditor Workspace';
        }

        return 'Executive DQA Cockpit';
    }

    public function persistsFiltersInSession(): bool
    {
        return false;
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }

    public function getWidgets(): array
    {
        $user = auth()->user();
        $widgets = [
            \App\Filament\Widgets\RoleContextBannerWidget::class,
        ];

        if (!$user || $user->isMealOfficer()) {
            // MEAL Officer (System Administrator & Central Dispatcher)
            $widgets[] = \App\Filament\Widgets\DqaOverviewStatsWidget::class;
            $widgets[] = \App\Filament\Widgets\DimensionScorecardWidget::class;
            $widgets[] = \App\Filament\Widgets\DimensionPerformanceChartWidget::class;
            $widgets[] = \App\Filament\Widgets\ProjectComparativeTableWidget::class;
            $widgets[] = \App\Filament\Widgets\MealAuditDispatchWidget::class;
        } elseif ($user->isProjectOfficer()) {
            // Project Officer (Data Submitter, Project Overseer & CAPA Respondent)
            $widgets[] = \App\Filament\Widgets\ProjectOfficerStatsWidget::class;
            $widgets[] = \App\Filament\Widgets\DimensionScorecardWidget::class;
            $widgets[] = \App\Filament\Widgets\ProjectSiteBreakdownWidget::class;
            $widgets[] = \App\Filament\Widgets\ProjectCapaTrackerWidget::class;
            $widgets[] = \App\Filament\Widgets\DimensionPerformanceChartWidget::class;

            // If dual-role (also Peer Auditor), include their peer audit task queue
            if ($user->isAuditor()) {
                $widgets[] = \App\Filament\Widgets\AuditorQueueTableWidget::class;
            }
        } elseif ($user->isAuditor()) {
            // Field / Peer Auditor (Field Evaluator & Findings Submitter)
            $widgets[] = \App\Filament\Widgets\AuditorStatsWidget::class;
            $widgets[] = \App\Filament\Widgets\AuditorQueueTableWidget::class;
            $widgets[] = \App\Filament\Widgets\AuditorRecentAuditsWidget::class;
            $widgets[] = \App\Filament\Widgets\DimensionScorecardWidget::class;
            $widgets[] = \App\Filament\Widgets\DimensionPerformanceChartWidget::class;
        }

        return $widgets;
    }

    public function filtersForm(Form $form): Form
    {
        $user = auth()->user();
        $isAuditorOnly = $user && $user->isAuditor() && ! $user->isMealOfficer() && ! $user->isProjectOfficer();

        $years = Audit::query()
            ->distinct()
            ->whereNotNull('period_year')
            ->orderByDesc('period_year')
            ->pluck('period_year', 'period_year')
            ->toArray();

        if (empty($years)) {
            $years = [now()->year => now()->year];
        }

        $filterFields = [];

        if ($isAuditorOnly) {
            $filterFields[] = Select::make('execution_status')
                ->label('Audit Status')
                ->placeholder('All Assigned & Done')
                ->selectablePlaceholder(true)
                ->default(null)
                ->options([
                    'ASSIGNED' => 'Assigned (Pending Verification)',
                    'DONE' => 'Done (Completed / Closed)',
                ]);
        } else {
            $filterFields[] = Select::make('project_id')
                ->label('Project')
                ->placeholder('All Projects')
                ->selectablePlaceholder(true)
                ->default(null)
                ->options(function () {
                    $user = auth()->user();
                    $query = Project::where('is_active', true);
                    if ($user && ! $user->isMealOfficer() && $user->isProjectOfficer()) {
                        $query->where('project_officer_id', $user->id);
                    }
                    return $query->pluck('name', 'id');
                });
        }

        $filterFields[] = Select::make('period_year')
            ->label('Calendar Year')
            ->placeholder('All Years')
            ->selectablePlaceholder(true)
            ->default(null)
            ->options($years);

        $filterFields[] = Select::make('period_quarter')
            ->label('Quarter')
            ->placeholder('All Quarters')
            ->selectablePlaceholder(true)
            ->default(null)
            ->options([
                1 => 'Q1 (Jan - Mar)',
                2 => 'Q2 (Apr - Jun)',
                3 => 'Q3 (Jul - Sep)',
                4 => 'Q4 (Oct - Dec)',
            ]);

        $filterFields[] = Select::make('period_month')
            ->label('Specific Month')
            ->placeholder('All Months')
            ->selectablePlaceholder(true)
            ->default(null)
            ->options([
                1 => 'January',
                2 => 'February',
                3 => 'March',
                4 => 'April',
                5 => 'May',
                6 => 'June',
                7 => 'July',
                8 => 'August',
                9 => 'September',
                10 => 'October',
                11 => 'November',
                12 => 'December',
            ]);

        $sectionHeading = $isAuditorOnly
            ? 'Auditor Quality & Verification Filters'
            : 'Multi-Tier Filters (Programmatic & Temporal)';

        $sectionDescription = $isAuditorOnly
            ? 'Filter your assigned and completed evaluations across calendar years, quarters, and specific months'
            : 'Filter dashboard metrics across all portfolio projects, calendar years, quarters, and specific months';

        return $form
            ->schema([
                Section::make($sectionHeading)
                    ->description($sectionDescription)
                    ->schema($filterFields)
                    ->columns(4)
                    ->collapsible(),
            ]);
    }
}
