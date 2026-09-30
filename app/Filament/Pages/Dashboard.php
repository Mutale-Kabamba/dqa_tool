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

    protected static ?string $title = 'Executive DQA Cockpit';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Dashboard';

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
        return [
            \App\Filament\Widgets\DqaOverviewStatsWidget::class,
            \App\Filament\Widgets\DimensionScorecardWidget::class,
            \App\Filament\Widgets\DimensionPerformanceChartWidget::class,
            \App\Filament\Widgets\ProjectComparativeTableWidget::class,
        ];
    }

    public function filtersForm(Form $form): Form
    {
        $years = Audit::query()
            ->distinct()
            ->whereNotNull('period_year')
            ->orderByDesc('period_year')
            ->pluck('period_year', 'period_year')
            ->toArray();

        if (empty($years)) {
            $years = [now()->year => now()->year];
        }

        return $form
            ->schema([
                Section::make('Multi-Tier Filters (Programmatic & Temporal)')
                    ->description('Filter dashboard metrics across all portfolio projects, calendar years, quarters, and specific months')
                    ->schema([
                        Select::make('project_id')
                            ->label('Project')
                            ->placeholder('All Projects')
                            ->selectablePlaceholder(true)
                            ->default(null)
                            ->options(Project::where('is_active', true)->pluck('name', 'id')),

                        Select::make('period_year')
                            ->label('Calendar Year')
                            ->placeholder('All Years')
                            ->selectablePlaceholder(true)
                            ->default(null)
                            ->options($years),

                        Select::make('period_quarter')
                            ->label('Quarter')
                            ->placeholder('All Quarters')
                            ->selectablePlaceholder(true)
                            ->default(null)
                            ->options([
                                1 => 'Q1 (Jan - Mar)',
                                2 => 'Q2 (Apr - Jun)',
                                3 => 'Q3 (Jul - Sep)',
                                4 => 'Q4 (Oct - Dec)',
                            ]),

                        Select::make('period_month')
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
                            ]),
                    ])
                    ->columns(4)
                    ->collapsible(),
            ]);
    }
}
