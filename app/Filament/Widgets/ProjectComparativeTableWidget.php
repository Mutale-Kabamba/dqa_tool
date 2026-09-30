<?php

namespace App\Filament\Widgets;

use App\Models\Audit;
use App\Models\Project;
use App\Services\DqaEngineService;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class ProjectComparativeTableWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Programmatic Performance Summary';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        $filters = $this->filters;

        return $table
            ->query(
                Project::query()
                    ->where('is_active', true)
                    ->when(!empty($filters['project_id']), fn ($q) => $q->where('id', $filters['project_id']))
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Project')
                    ->weight(FontWeight::Bold)
                    ->description(fn (Project $record) => $record->code),

                Tables\Columns\TextColumn::make('filtered_audits_count')
                    ->label('Visits')
                    ->state(function (Project $record) use ($filters) {
                        return Audit::query()
                            ->where('project_id', $record->id)
                            ->filtered($filters)
                            ->count();
                    })
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('pooled_score')
                    ->label('Score %')
                    ->state(function (Project $record) use ($filters) {
                        $audits = Audit::query()
                            ->where('project_id', $record->id)
                            ->filtered($filters);

                        $checked = (int) (clone $audits)->sum('overall_checked');
                        $compliant = (int) (clone $audits)->sum('overall_compliant');

                        if ($checked === 0) {
                            return null;
                        }

                        return round($compliant / $checked, 4);
                    })
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state * 100, 1) . '%' : 'N/A')
                    ->weight(FontWeight::Bold)
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('portfolio_status')
                    ->label('Status')
                    ->state(function (Project $record) use ($filters) {
                        $audits = Audit::query()
                            ->where('project_id', $record->id)
                            ->filtered($filters);

                        $checked = (int) (clone $audits)->sum('overall_checked');
                        $compliant = (int) (clone $audits)->sum('overall_compliant');

                        if ($checked === 0) {
                            return 'PENDING';
                        }

                        $engine = new DqaEngineService();
                        return $engine->computeStatus($compliant / $checked);
                    })
                    ->badge()
                    ->color(fn (string $state): string | array => match ($state) {
                        'GREEN' => 'success',
                        'YELLOW' => 'warning',
                        'ORANGE' => \Filament\Support\Colors\Color::Orange,
                        'RED' => 'danger',
                        default => 'gray',
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('priority_summary')
                    ->label('Focus Areas')
                    ->state(function (Project $record) use ($filters) {
                        $audits = Audit::query()
                            ->where('project_id', $record->id)
                            ->filtered($filters)
                            ->whereNotNull('priority_areas')
                            ->where('priority_areas', '!=', '')
                            ->pluck('priority_areas');

                        if ($audits->isEmpty()) {
                            return 'None';
                        }

                        $areas = [];
                        foreach ($audits as $p) {
                            foreach (explode(',', $p) as $item) {
                                $item = trim($item);
                                if ($item) {
                                    $areas[$item] = ($areas[$item] ?? 0) + 1;
                                }
                            }
                        }

                        return !empty($areas) ? implode(', ', array_keys($areas)) : 'None';
                    })
                    ->limit(25)
                    ->placeholder('None'),
            ])
            ->paginated(false)
            ->actions([
                Tables\Actions\Action::make('view_audits')
                    ->label('Audits')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Project $record) => route('filament.admin.resources.audits.index', [
                        'tableFilters' => ['project_id' => ['value' => $record->id]],
                    ])),
            ]);
    }
}
