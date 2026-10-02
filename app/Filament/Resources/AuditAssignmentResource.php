<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditAssignmentResource\Pages;
use App\Models\Audit;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditAssignmentResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationGroup = 'Central Quality Operations';

    protected static ?string $navigationLabel = 'Audit Dispatch Engine';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isMealOfficer() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['project', 'projectOfficer', 'auditor'])
            ->where(function ($q) {
                $q->where('workflow_status', Audit::STATUS_PENDING_ASSIGNMENT)
                  ->orWhereNull('auditor_id');
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('audit_code')
                    ->label('Submission ID')
                    ->badge()
                    ->color('gray')
                    ->weight(FontWeight::Bold)
                    ->searchable(),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(),

                Tables\Columns\TextColumn::make('site_name')
                    ->label('Facility / Site Audited')
                    ->searchable(),

                Tables\Columns\TextColumn::make('projectOfficer.name')
                    ->label('Project Officer')
                    ->placeholder('Unassigned')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-user-group'),

                Tables\Columns\TextColumn::make('period_label')
                    ->label('Period')
                    ->badge(),

                Tables\Columns\IconColumn::make('data_file_path')
                    ->label('File')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('assignAuditor')
                    ->label('Assign & Dispatch')
                    ->icon('heroicon-m-user-plus')
                    ->color('primary')
                    ->form(fn (Audit $record): array => [
                        Forms\Components\Select::make('auditor_id')
                            ->label('Select Independent Auditor')
                            ->options(function () use ($record) {
                                $excludedOfficerId = $record->project_officer_id ?? $record->project?->project_officer_id;

                                return User::query()
                                    ->where('is_active', true)
                                    ->where(function ($q) {
                                        $q->whereJsonContains('roles', User::ROLE_AUDITOR)
                                          ->orWhereJsonContains('roles', User::ROLE_MEAL_OFFICER);
                                    })
                                    ->when($excludedOfficerId, fn ($q) => $q->where('id', '!=', $excludedOfficerId))
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->helperText('Strict Anti-Self-Audit Policy: Project Officers cannot be assigned to audit their own projects.'),
                    ])
                    ->action(function (Audit $record, array $data): void {
                        $auditor = User::find($data['auditor_id']);
                        $record->update([
                            'auditor_id' => $data['auditor_id'],
                            'auditor_name' => $auditor?->name ?? $record->auditor_name,
                            'workflow_status' => Audit::STATUS_ASSIGNED_TO_AUDITOR,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Auditor Dispatched (Step 2 Completed)')
                            ->body("Successfully assigned {$auditor?->name} to audit {$record->audit_code}.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('downloadFile')
                    ->label('File')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn (Audit $record) => ! empty($record->data_file_path))
                    ->url(fn (Audit $record) => asset('storage/' . $record->data_file_path))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('view')
                    ->label('Dossier')
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
            'index' => Pages\ListAuditAssignments::route('/'),
        ];
    }
}
