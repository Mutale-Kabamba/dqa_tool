<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AuditResource;
use App\Models\Audit;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class AuditorQueueTableWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'My Assigned Audit Tasks and Verification Queue';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $filters = $this->filters;

        $query = Audit::query()
            ->with(['project', 'projectOfficer'])
            ->filtered($filters);

        if ($user && !$user->isMealOfficer()) {
            $query->where(function ($q) use ($user) {
                $q->where('auditor_id', $user->id)
                  ->orWhere('auditor_name', $user->name);
            });
        }

        return $table
            ->query($query->latest('audit_date'))
            ->columns([
                Tables\Columns\TextColumn::make('audit_code')
                    ->label('Audit Code')
                    ->badge()
                    ->color('gray')
                    ->weight(FontWeight::Bold)
                    ->searchable(),

                Tables\Columns\TextColumn::make('workflow_status')
                    ->label('Stage')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Audit::STATUS_PENDING_ASSIGNMENT => 'warning',
                        Audit::STATUS_ASSIGNED_TO_AUDITOR => 'info',
                        Audit::STATUS_AUDIT_COMPLETED => 'primary',
                        Audit::STATUS_CAPA_SUBMITTED => 'warning',
                        Audit::STATUS_AUDIT_CLOSED => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Audit::STATUS_PENDING_ASSIGNMENT => '1. Pending Dispatch',
                        Audit::STATUS_ASSIGNED_TO_AUDITOR => '2. Assigned (Action Required)',
                        Audit::STATUS_AUDIT_COMPLETED => '3. Findings Submitted',
                        Audit::STATUS_CAPA_SUBMITTED => '4. CAPA In Review',
                        Audit::STATUS_AUDIT_CLOSED => '5. Closed & Certified',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(),

                Tables\Columns\TextColumn::make('site_name')
                    ->label('Facility / Site')
                    ->searchable(),

                Tables\Columns\TextColumn::make('projectOfficer.name')
                    ->label('Project Officer')
                    ->default(fn ($record) => $record->facility_in_charge)
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-user-group'),

                Tables\Columns\IconColumn::make('data_file_path')
                    ->label('File Attached')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-arrow-down')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => $record->data_file_path ? 'Raw submission data file attached' : 'No data file attached'),

                Tables\Columns\TextColumn::make('audit_date')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('overall_score')
                    ->label('Score')
                    ->formatStateUsing(fn ($state) => number_format(((float) $state) * 100, 1) . '%')
                    ->alignCenter()
                    ->weight(FontWeight::Bold),

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
            ])
            ->actions([
                // STEP 3 ACTION: Download / Open Data File
                Tables\Actions\Action::make('downloadSubmissionFile')
                    ->label('File')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->visible(fn (Audit $record) => ! empty($record->data_file_path))
                    ->url(fn (Audit $record) => asset('storage/' . $record->data_file_path))
                    ->openUrlInNewTab(),

                // STEP 3 ACTION: Input / Edit Findings
                Tables\Actions\Action::make('enterFindings')
                    ->label('Tally Check')
                    ->icon('heroicon-m-pencil-square')
                    ->color('primary')
                    ->url(fn (Audit $record): string => AuditResource::getUrl('edit', ['record' => $record])),

                // STEP 3 ACTION: Submit Audit Findings
                Tables\Actions\Action::make('submitAuditFindings')
                    ->label('Submit Findings')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn (Audit $record) => in_array($record->workflow_status, [Audit::STATUS_ASSIGNED_TO_AUDITOR, Audit::STATUS_PENDING_ASSIGNMENT]))
                    ->requiresConfirmation()
                    ->modalHeading('Submit Audit Findings (Step 3)')
                    ->modalDescription('Confirm all 5 dimensional tallies and qualitative observations are recorded.')
                    ->action(function (Audit $record): void {
                        $record->update([
                            'workflow_status' => Audit::STATUS_AUDIT_COMPLETED,
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Audit Findings Submitted (Step 3 Completed)')
                            ->body("Audit {$record->audit_code} submitted for review & CAPA.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('viewDossier')
                    ->label('Dossier')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->url(fn (Audit $record): string => AuditResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('No Audits Assigned in Queue')
            ->emptyStateDescription('When the MEAL Officer dispatches audit tasks to you, they will appear here for dimensional tally and qualitative reporting.');
    }
}
