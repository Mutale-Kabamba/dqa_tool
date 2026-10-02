<?php

namespace App\Filament\Pages;

use App\Models\Audit;
use App\Models\AuditDimension;
use App\Models\Project;
use App\Services\DqaEngineService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConsolidatedProjectView extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationGroup = 'Program Management';

    protected static ?string $navigationLabel = 'Consolidated Project View';

    protected static ?string $title = 'Consolidated Project Performance Review';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.consolidated-project-view';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isMealOfficer() || $user->isProjectOfficer());
    }

    public static function getNavigationLabel(): string
    {
        $user = auth()->user();
        if ($user && $user->isProjectOfficer() && ! $user->isMealOfficer()) {
            return 'Project Performance & Review';
        }

        return 'Consolidated Project View';
    }

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();
        if ($user && $user->isProjectOfficer() && ! $user->isMealOfficer()) {
            return 'My Project Operations';
        }

        return 'Program Management';
    }

    public ?int $project_id = null;
    public ?int $period_year = null;
    public ?int $period_quarter = null;
    public ?int $period_month = null;

    public function mount(): void
    {
        $user = auth()->user();
        if ($user && $user->isProjectOfficer() && ! $user->isMealOfficer()) {
            $defaultProjectId = $user->managedProjects()->where('is_active', true)->value('id')
                ?? Project::where('is_active', true)->value('id');
        } else {
            $defaultProjectId = Project::where('is_active', true)->value('id');
        }

        $this->project_id = $defaultProjectId;
        $this->form->fill([
            'project_id' => $this->project_id,
            'period_year' => null,
            'period_quarter' => null,
            'period_month' => null,
        ]);
    }

    public function form(Form $form): Form
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
                Forms\Components\Section::make('Project & Multi-Tier Temporal Filter')
                    ->description('Select a project and specify the reporting timeframe to review aggregated performance and site-level findings')
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->label('Project')
                            ->options(function () {
                                $user = auth()->user();
                                $query = Project::where('is_active', true);
                                if ($user && ! $user->isMealOfficer() && $user->isProjectOfficer()) {
                                    $query->where('project_officer_id', $user->id);
                                }
                                return $query->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->project_id = (int) $state;
                                $this->resetTable();
                            }),

                        Forms\Components\Select::make('period_year')
                            ->label('Calendar Year')
                            ->placeholder('All Years')
                            ->options($years)
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->period_year = $state ? (int) $state : null;
                                $this->resetTable();
                            }),

                        Forms\Components\Select::make('period_quarter')
                            ->label('Quarter')
                            ->placeholder('All Quarters')
                            ->options([
                                1 => 'Q1 (Jan - Mar)',
                                2 => 'Q2 (Apr - Jun)',
                                3 => 'Q3 (Jul - Sep)',
                                4 => 'Q4 (Oct - Dec)',
                            ])
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->period_quarter = $state ? (int) $state : null;
                                $this->resetTable();
                            }),

                        Forms\Components\Select::make('period_month')
                            ->label('Specific Month')
                            ->placeholder('All Months')
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
                            ])
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->period_month = $state ? (int) $state : null;
                                $this->resetTable();
                            }),
                    ])
                    ->columns(4),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Audit::query()
                    ->when($this->project_id, fn ($q) => $q->where('project_id', $this->project_id))
                    ->when($this->period_year, fn ($q) => $q->where('period_year', $this->period_year))
                    ->when($this->period_quarter, fn ($q) => $q->where('period_quarter', $this->period_quarter))
                    ->when($this->period_month, fn ($q) => $q->where('period_month', $this->period_month))
            )
            ->heading('Site-by-Site Verification Breakdown')
            ->description('Individual health facility and site audit results for the selected project and temporal horizon')
            ->columns([
                Tables\Columns\TextColumn::make('site_name')
                    ->label('Facility / Site')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('audit_code')
                    ->label('Audit Code')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('period_label')
                    ->label('Period')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('audit_date')
                    ->label('Audit Date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('auditor_name')
                    ->label('Auditor')
                    ->searchable(),

                Tables\Columns\TextColumn::make('overall_checked')
                    ->label('Checked')
                    ->alignRight()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('overall_compliant')
                    ->label('Compliant')
                    ->alignRight()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('overall_score')
                    ->label('Score')
                    ->formatStateUsing(fn ($state) => number_format(((float) $state) * 100, 1) . '%')
                    ->alignCenter()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('overall_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string | array => match ($state) {
                        'GREEN' => 'success',
                        'YELLOW' => 'warning',
                        'ORANGE' => \Filament\Support\Colors\Color::Orange,
                        'RED' => 'danger',
                        default => 'gray',
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('priority_areas')
                    ->label('Flagged Areas')
                    ->placeholder('None (< 85%)')
                    ->limit(30),
            ])
            ->defaultSort('audit_date', 'desc')
            ->actions([
                Tables\Actions\Action::make('view_audit')
                    ->label('Dossier')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Audit $record) => route('filament.admin.resources.audits.view', ['record' => $record])),
            ]);
    }

    public function getConsolidatedMetricsProperty(): array
    {
        $project = Project::find($this->project_id);
        if (! $project) {
            return [];
        }

        $engine = new DqaEngineService();
        $filters = [
            'project_id' => $this->project_id,
            'period_year' => $this->period_year,
            'period_quarter' => $this->period_quarter,
            'period_month' => $this->period_month,
        ];

        $auditsQuery = Audit::query()->filtered($filters);
        $totalVisits = (clone $auditsQuery)->count();
        $totalChecked = (int) (clone $auditsQuery)->sum('overall_checked');
        $totalCompliant = (int) (clone $auditsQuery)->sum('overall_compliant');
        $overallScore = $totalVisits > 0 ? round((float) (clone $auditsQuery)->avg('overall_score'), 4) : 0.0;
        $overallStatus = $engine->computeStatus($overallScore);

        $criticalCount = (clone $auditsQuery)
            ->where(function ($q) {
                $q->whereIn('overall_status', ['ORANGE', 'RED'])
                    ->orWhereHas('dimensions', function ($dimQuery) {
                        $dimQuery->whereIn('status', ['ORANGE', 'RED']);
                    });
            })
            ->count();

        // 5-Dimension pooled breakdown
        $dimQuery = AuditDimension::query()
            ->join('audits', 'audits.id', '=', 'audit_dimensions.audit_id')
            ->where('audits.project_id', $this->project_id);

        if (!empty($this->period_year)) {
            $dimQuery->where('audits.period_year', $this->period_year);
        }
        if (!empty($this->period_quarter)) {
            $dimQuery->where('audits.period_quarter', $this->period_quarter);
        }
        if (!empty($this->period_month)) {
            $dimQuery->where('audits.period_month', $this->period_month);
        }

        $dimRecords = $dimQuery
            ->selectRaw('
                audit_dimensions.dimension_name,
                SUM(audit_dimensions.checked_count) as total_checked,
                SUM(audit_dimensions.compliant_count) as total_compliant
            ')
            ->groupBy('audit_dimensions.dimension_name')
            ->get()
            ->keyBy('dimension_name');

        $dimensions = [];
        foreach (['Accuracy', 'Completeness', 'Consistency', 'Timeliness', 'Validity'] as $name) {
            $rec = $dimRecords->get($name);
            $c = $rec ? (int) $rec->total_checked : 0;
            $comp = $rec ? (int) $rec->total_compliant : 0;
            $s = $c > 0 ? round($comp / $c, 4) : 0.0;
            $dimensions[] = [
                'name' => $name,
                'checked' => $c,
                'compliant' => $comp,
                'score' => $s,
                'score_pct' => number_format($s * 100, 1),
                'status' => $engine->computeStatus($s),
            ];
        }

        // Recurring Priority Analytics
        $priorityCounts = [];
        $allAudits = (clone $auditsQuery)
            ->whereNotNull('priority_areas')
            ->where('priority_areas', '!=', '')
            ->pluck('priority_areas');

        foreach ($allAudits as $pList) {
            foreach (explode(',', $pList) as $item) {
                $item = trim($item);
                if ($item) {
                    $priorityCounts[$item] = ($priorityCounts[$item] ?? 0) + 1;
                }
            }
        }

        $recurringPriorities = [];
        foreach ($priorityCounts as $item => $count) {
            $pct = $totalVisits > 0 ? round(($count / $totalVisits) * 100, 1) : 0;
            $recurringPriorities[] = [
                'dimension' => $item,
                'count' => $count,
                'total' => $totalVisits,
                'percentage' => $pct,
            ];
        }

        usort($recurringPriorities, fn ($a, $b) => $b['count'] <=> $a['count']);

        // Facility Longitudinal Quality Trajectory (Site Historical Progress)
        $facilityAudits = (clone $auditsQuery)
            ->with('dimensions')
            ->orderBy('audit_date', 'asc')
            ->get()
            ->groupBy('site_name');

        $facilityTrajectories = [];
        foreach ($facilityAudits as $siteName => $siteRecords) {
            $sorted = $siteRecords->sortBy('audit_date')->values();
            $count = $sorted->count();
            $latest = $sorted->last();
            $prev = $count > 1 ? $sorted[$count - 2] : null;

            $latestScore = (float) $latest->overall_score;
            $prevScore = $prev ? (float) $prev->overall_score : null;
            $delta = $prevScore !== null ? round(($latestScore - $prevScore) * 100, 1) : 0.0;

            $trend = 'new';
            if ($prevScore !== null) {
                if ($delta > 0.5) {
                    $trend = 'improving';
                } elseif ($delta < -0.5) {
                    $trend = 'deteriorating';
                } else {
                    $trend = 'stable';
                }
            }

            $history = $sorted->map(fn ($rec) => [
                'period' => $rec->period_label,
                'score_pct' => number_format(((float) $rec->overall_score) * 100, 1),
                'status' => $rec->overall_status,
                'audit_code' => $rec->audit_code,
            ])->toArray();

            $facilityTrajectories[] = [
                'site_name' => $siteName,
                'audit_count' => $count,
                'latest_period' => $latest->period_label,
                'latest_score_pct' => number_format($latestScore * 100, 1),
                'latest_status' => $latest->overall_status,
                'prev_score_pct' => $prevScore !== null ? number_format($prevScore * 100, 1) : null,
                'delta' => $delta,
                'trend' => $trend,
                'history' => $history,
            ];
        }

        // CAPA Action Items Summary
        $auditIds = (clone $auditsQuery)->pluck('id');
        $capaItems = \App\Models\AuditActionItem::whereIn('audit_id', $auditIds)->get();
        $capaSummary = [
            'total'       => $capaItems->count(),
            'open'        => $capaItems->where('status', 'OPEN')->count(),
            'in_progress' => $capaItems->where('status', 'IN_PROGRESS')->count(),
            'resolved'    => $capaItems->where('status', 'RESOLVED')->count(),
            'overdue'     => $capaItems->where('effective_status', 'OVERDUE')->count(),
        ];

        return [
            'project' => $project,
            'total_visits' => $totalVisits,
            'total_checked' => $totalChecked,
            'total_compliant' => $totalCompliant,
            'overall_score' => $overallScore,
            'overall_score_pct' => number_format($overallScore * 100, 1),
            'overall_status' => $overallStatus,
            'critical_count' => $criticalCount,
            'dimensions' => $dimensions,
            'recurring_priorities' => $recurringPriorities,
            'facility_trajectories' => $facilityTrajectories,
            'capa_summary' => $capaSummary,
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $filters = [
            'project_id' => $this->project_id,
            'period_year' => $this->period_year,
            'period_quarter' => $this->period_quarter,
            'period_month' => $this->period_month,
        ];

        $audits = Audit::query()
            ->filtered($filters)
            ->with(['project', 'dimensions'])
            ->orderBy('audit_date', 'desc')
            ->get();

        $project = Project::find($this->project_id);
        $projectNameSlug = $project ? str_replace(' ', '_', $project->name) : 'Project';
        $filename = "{$projectNameSlug}_DQA_Export_" . now()->format('Ymd_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($audits) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Audit ID',
                'Project Code',
                'Project Name',
                'Site / Facility',
                'Auditor Name',
                'Audit Date',
                'Period Label',
                'Year',
                'Quarter',
                'Month',
                'Accuracy Checked',
                'Accuracy Compliant',
                'Accuracy Score (%)',
                'Completeness Checked',
                'Completeness Compliant',
                'Completeness Score (%)',
                'Consistency Checked',
                'Consistency Compliant',
                'Consistency Score (%)',
                'Timeliness Checked',
                'Timeliness Compliant',
                'Timeliness Score (%)',
                'Validity Checked',
                'Validity Compliant',
                'Validity Score (%)',
                'Overall Checked',
                'Overall Compliant',
                'Overall Score (%)',
                'Overall Status',
                'Priority Areas',
                'Project Officer',
                'Root Cause Notes',
                'Recommendations',
            ]);

            foreach ($audits as $audit) {
                $dims = $audit->dimensions->keyBy('dimension_name');

                fputcsv($file, [
                    $audit->audit_code,
                    $audit->project?->code,
                    $audit->project?->name,
                    $audit->site_name,
                    $audit->auditor_name,
                    $audit->audit_date?->format('Y-m-d'),
                    $audit->period_label,
                    $audit->period_year,
                    $audit->period_quarter,
                    $audit->period_month,
                    $dims['Accuracy']->checked_count ?? 0,
                    $dims['Accuracy']->compliant_count ?? 0,
                    isset($dims['Accuracy']) ? number_format($dims['Accuracy']->score_percentage * 100, 1) . '%' : 'N/A',
                    $dims['Completeness']->checked_count ?? 0,
                    $dims['Completeness']->compliant_count ?? 0,
                    isset($dims['Completeness']) ? number_format($dims['Completeness']->score_percentage * 100, 1) . '%' : 'N/A',
                    $dims['Consistency']->checked_count ?? 0,
                    $dims['Consistency']->compliant_count ?? 0,
                    isset($dims['Consistency']) ? number_format($dims['Consistency']->score_percentage * 100, 1) . '%' : 'N/A',
                    $dims['Timeliness']->checked_count ?? 0,
                    $dims['Timeliness']->compliant_count ?? 0,
                    isset($dims['Timeliness']) ? number_format($dims['Timeliness']->score_percentage * 100, 1) . '%' : 'N/A',
                    $dims['Validity']->checked_count ?? 0,
                    $dims['Validity']->compliant_count ?? 0,
                    isset($dims['Validity']) ? number_format($dims['Validity']->score_percentage * 100, 1) . '%' : 'N/A',
                    $audit->overall_checked,
                    $audit->overall_compliant,
                    number_format(((float) $audit->overall_score) * 100, 1) . '%',
                    $audit->overall_status,
                    $audit->priority_areas,
                    $audit->facility_in_charge,
                    $audit->root_cause_notes,
                    $audit->recommendations,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label('Export Data to CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action('exportCsv'),

            Action::make('print')
                ->label('Print Project Rollup')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->extraAttributes(['onclick' => 'window.print(); return false;']),
        ];
    }
}
