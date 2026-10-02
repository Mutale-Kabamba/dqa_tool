<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AuditResource;
use App\Models\Audit;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class ProjectSiteBreakdownWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Site-by-Site Facility Audit Breakdown';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $filters = $this->filters;
        $user = auth()->user();
        $managedProjectIds = $user ? $user->managedProjects()->pluck('id')->toArray() : [];

        $query = Audit::query()
            ->with(['project', 'dimensions'])
            ->filtered($filters);

        if ($user && ! $user->isMealOfficer() && ! empty($managedProjectIds)) {
            $query->whereIn('project_id', $managedProjectIds);
        }

        return $table
            ->query($query->latest('audit_date'))
            ->columns([
                Tables\Columns\TextColumn::make('site_name')
                    ->label('Facility / Site')
                    ->weight(FontWeight::Bold)
                    ->searchable(),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->weight(FontWeight::SemiBold),

                Tables\Columns\TextColumn::make('period_label')
                    ->label('Reporting Period')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('audit_date')
                    ->label('Latest Audit Date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('overall_score')
                    ->label('Audit Score')
                    ->formatStateUsing(fn ($state) => number_format(((float) $state) * 100, 1) . '%')
                    ->weight(FontWeight::Bold)
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('overall_status')
                    ->label('RAG')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'GREEN' => 'success',
                        'YELLOW' => 'warning',
                        'ORANGE' => 'danger',
                        'RED' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('priority_areas')
                    ->label('Flagged Priority Areas (Gaps)')
                    ->badge()
                    ->color('danger')
                    ->placeholder('None (All dimensions >= 85%)'),
            ])
            ->actions([
                Tables\Actions\Action::make('viewDossier')
                    ->label('Dossier')
                    ->icon('heroicon-m-eye')
                    ->color('primary')
                    ->url(fn (Audit $record): string => AuditResource::getUrl('view', ['record' => $record])),

                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->url(fn (Audit $record) => route('admin.audits.pdf', ['audit' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('No Site Audits Recorded')
            ->emptyStateDescription('Audit findings for facilities under your assigned project will be displayed here upon auditor completion.');
    }
}
