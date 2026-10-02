<?php

namespace App\Filament\Widgets;

use App\Models\AuditActionItem;
use Filament\Forms;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ProjectCapaTrackerWidget extends BaseWidget
{
    protected static ?string $heading = 'Project CAPA Remediation and Action Items';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $managedProjectIds = $user ? $user->managedProjects()->pluck('id')->toArray() : [];

        $query = AuditActionItem::query()
            ->with(['audit.project'])
            ->when($user && !$user->isMealOfficer() && !empty($managedProjectIds), function ($q) use ($managedProjectIds) {
                $q->whereHas('audit', function ($auditQ) use ($managedProjectIds) {
                    $auditQ->whereIn('project_id', $managedProjectIds);
                });
            })
            ->latest('due_date');

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('audit.site_name')
                    ->label('Site / Facility')
                    ->weight(FontWeight::Bold)
                    ->description(fn (AuditActionItem $record) => $record->audit?->audit_code . ' • ' . $record->audit?->project?->name)
                    ->searchable(),

                Tables\Columns\TextColumn::make('dimension_name')
                    ->label('Dimension')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('issue_description')
                    ->label('Identified Problem')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->issue_description),

                Tables\Columns\TextColumn::make('action_plan')
                    ->label('Corrective Action')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->action_plan),

                Tables\Columns\TextColumn::make('responsible_person')
                    ->label('Assigned To')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('CAPA Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'RESOLVED' => 'success',
                        'IN_PROGRESS' => 'info',
                        'OVERDUE' => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('updateStatus')
                    ->label('Update')
                    ->icon('heroicon-m-pencil-square')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Remediation Status')
                            ->options([
                                'OPEN' => 'Open',
                                'IN_PROGRESS' => 'In Progress',
                                'RESOLVED' => 'Resolved',
                                'OVERDUE' => 'Overdue',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('resolution_notes')
                            ->label('Remediation Notes / Evidence')
                            ->placeholder('Describe corrective steps taken and verification details...')
                            ->required(),
                    ])
                    ->fillForm(fn (AuditActionItem $record): array => [
                        'status' => $record->status,
                        'resolution_notes' => $record->resolution_notes,
                    ])
                    ->action(function (AuditActionItem $record, array $data): void {
                        $record->update($data);
                        \Filament\Notifications\Notification::make()
                            ->title('CAPA Item Updated')
                            ->success()
                            ->send();
                    }),

                // STEP 4: Submit CAPA Response on Audit
                Tables\Actions\Action::make('submitAuditCapa')
                    ->label('Submit Plan')
                    ->icon('heroicon-m-arrow-path')
                    ->color('warning')
                    ->visible(fn (AuditActionItem $record) => $record->audit && in_array($record->audit->workflow_status, [Audit::STATUS_AUDIT_COMPLETED, Audit::STATUS_CAPA_SUBMITTED]))
                    ->form(fn (AuditActionItem $record): array => [
                        Forms\Components\Textarea::make('root_cause_notes')
                            ->label('Operational Root Cause Context')
                            ->default($record->audit?->root_cause_notes)
                            ->required(),
                        Forms\Components\Textarea::make('recommendations')
                            ->label('Remedial Action Commitments')
                            ->default($record->audit?->recommendations)
                            ->required(),
                    ])
                    ->action(function (AuditActionItem $record, array $data): void {
                        if ($record->audit) {
                            $record->audit->update([
                                'root_cause_notes' => $data['root_cause_notes'],
                                'recommendations' => $data['recommendations'],
                                'workflow_status' => Audit::STATUS_CAPA_SUBMITTED,
                            ]);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('CAPA Plan Submitted (Step 4 Completed)')
                            ->body("Remedial action plan submitted for audit {$record->audit?->audit_code}.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('viewAudit')
                    ->label('Dossier')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->url(fn (AuditActionItem $record): string => $record->audit ? \App\Filament\Resources\AuditResource::getUrl('view', ['record' => $record->audit]) : '#'),
            ])
            ->emptyStateHeading('No Open CAPA Items')
            ->emptyStateDescription('All data quality dimensions in this project are meeting national benchmarks with no outstanding corrective actions.');
    }
}
