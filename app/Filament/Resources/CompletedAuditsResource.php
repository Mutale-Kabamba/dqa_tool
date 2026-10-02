<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompletedAuditsResource\Pages;
use App\Models\Audit;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CompletedAuditsResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationGroup = 'Field Audits';

    protected static ?string $navigationLabel = 'Completed Audits Archive';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAuditor() || $user->isMealOfficer());
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['project', 'projectOfficer', 'certifiedBy'])
            ->whereIn('workflow_status', [Audit::STATUS_AUDIT_COMPLETED, Audit::STATUS_CAPA_SUBMITTED, Audit::STATUS_AUDIT_CLOSED]);

        $user = auth()->user();
        if ($user && ! $user->isMealOfficer()) {
            $query->where(function ($q) use ($user) {
                $q->where('auditor_id', $user->id)
                  ->orWhere('auditor_name', $user->name);
            });
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('audit_code')
                    ->label('Audit Code')
                    ->badge()
                    ->color('gray')
                    ->weight(FontWeight::Bold)
                    ->searchable(),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(),

                Tables\Columns\TextColumn::make('site_name')
                    ->label('Facility / Site')
                    ->searchable(),

                Tables\Columns\TextColumn::make('period_label')
                    ->label('Period')
                    ->badge(),

                Tables\Columns\TextColumn::make('audit_date')
                    ->label('Audit Date')
                    ->date('M j, Y')
                    ->sortable(),

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
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Audit::STATUS_AUDIT_COMPLETED => 'primary',
                        Audit::STATUS_CAPA_SUBMITTED => 'warning',
                        Audit::STATUS_AUDIT_CLOSED => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Audit::STATUS_AUDIT_COMPLETED => 'Audit Findings Submitted',
                        Audit::STATUS_CAPA_SUBMITTED => 'CAPA Under Review',
                        Audit::STATUS_AUDIT_CLOSED => 'Certified & Approved',
                        default => $state,
                    }),
            ])
            ->defaultSort('audit_date', 'desc')
            ->actions([
                Tables\Actions\Action::make('pdf')
                    ->label('PDF Dossier')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->url(fn (Audit $record) => route('admin.audits.pdf', ['audit' => $record]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('view')
                    ->label('View Dossier')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->url(fn (Audit $record): string => AuditResource::getUrl('view', ['record' => $record])),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompletedAudits::route('/'),
        ];
    }
}
