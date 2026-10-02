<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignedAuditsResource\Pages;
use App\Models\Audit;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssignedAuditsResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Field Audits';

    protected static ?string $navigationLabel = 'Assigned Audit Tasks';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAuditor() || $user->isMealOfficer());
    }

    public static function getNavigationLabel(): string
    {
        $user = auth()->user();
        if ($user && $user->isProjectOfficer() && $user->isAuditor() && ! $user->isMealOfficer()) {
            return 'My Assigned Peer Audits';
        }

        return 'Assigned Audit Tasks';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['project', 'projectOfficer', 'dimensions'])
            ->whereIn('workflow_status', [Audit::STATUS_ASSIGNED_TO_AUDITOR, Audit::STATUS_PENDING_ASSIGNMENT]);

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
                    ->label('Reporting Period')
                    ->badge(),

                Tables\Columns\IconColumn::make('data_file_path')
                    ->label('Data File')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-arrow-down')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('audit_date')
                    ->label('Audit Date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('workflow_status')
                    ->label('Task Status')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => 'Assigned (Action Required)'),
            ])
            ->defaultSort('audit_date', 'asc')
            ->actions([
                Tables\Actions\Action::make('downloadFile')
                    ->label('Download Data')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->visible(fn (Audit $record) => ! empty($record->data_file_path))
                    ->url(fn (Audit $record) => asset('storage/' . $record->data_file_path))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('performAudit')
                    ->label('Perform Audit')
                    ->icon('heroicon-m-pencil-square')
                    ->color('success')
                    ->url(fn (Audit $record): string => AuditResource::getUrl('edit', ['record' => $record])),

                Tables\Actions\Action::make('submitFindings')
                    ->label('Submit Findings')
                    ->icon('heroicon-m-check-badge')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Submit Audit Findings')
                    ->modalDescription('Confirm you have completed dimensional evaluation and qualitative observations.')
                    ->action(function (Audit $record): void {
                        $record->update(['workflow_status' => Audit::STATUS_AUDIT_COMPLETED]);
                        \Filament\Notifications\Notification::make()
                            ->title('Audit Findings Submitted')
                            ->body("Findings submitted for {$record->audit_code}.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('view')
                    ->label('Dossier')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->url(fn (Audit $record): string => AuditResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('No Pending Audit Tasks')
            ->emptyStateDescription('All dispatched audit assignments have been evaluated.');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssignedAudits::route('/'),
        ];
    }
}
