<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AuditResource;
use App\Models\Audit;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class AuditorRecentAuditsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Recent Audits Summary (Last 5 Conducted)';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $filters = $this->filters;

        $query = Audit::query()
            ->with(['project', 'certifiedBy'])
            ->whereIn('workflow_status', [
                Audit::STATUS_AUDIT_COMPLETED,
                Audit::STATUS_CAPA_SUBMITTED,
                Audit::STATUS_AUDIT_CLOSED,
            ])
            ->filtered($filters);

        if ($user && ! $user->isMealOfficer()) {
            $query->where(function ($q) use ($user) {
                $q->where('auditor_id', $user->id)
                  ->orWhere('auditor_name', $user->name);
            });
        }

        return $table
            ->query($query->latest('audit_date')->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('audit_code')
                    ->label('Audit Code')
                    ->badge()
                    ->color('gray')
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->weight(FontWeight::SemiBold),

                Tables\Columns\TextColumn::make('site_name')
                    ->label('Facility / Site'),

                Tables\Columns\TextColumn::make('period_label')
                    ->label('Period')
                    ->badge(),

                Tables\Columns\TextColumn::make('overall_score')
                    ->label('Score')
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

                Tables\Columns\TextColumn::make('workflow_status')
                    ->label('MEAL Sign-Off / Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Audit::STATUS_AUDIT_CLOSED => 'success',
                        Audit::STATUS_CAPA_SUBMITTED => 'warning',
                        default => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Audit::STATUS_AUDIT_CLOSED => 'Signed Off & Certified',
                        Audit::STATUS_CAPA_SUBMITTED => 'CAPA Response in Review',
                        Audit::STATUS_AUDIT_COMPLETED => 'Findings Awaiting Sign-Off',
                        default => $state,
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('dossier')
                    ->label('Dossier')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->url(fn (Audit $record): string => AuditResource::getUrl('view', ['record' => $record])),

                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->url(fn (Audit $record) => route('admin.audits.pdf', ['audit' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('No Completed Audits Yet')
            ->emptyStateDescription('Audits you submit findings for will appear here along with their sign-off and certification status.');
    }
}
