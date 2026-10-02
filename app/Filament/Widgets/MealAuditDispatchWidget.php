<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AuditResource;
use App\Models\Audit;
use App\Models\User;
use Filament\Forms;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MealAuditDispatchWidget extends BaseWidget
{
    protected static ?string $heading = 'Central Dispatcher: Audit Submissions & Field Assignments';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Audit::query()->with(['project', 'auditor', 'projectOfficer'])->latest('audit_date'))
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
                        Audit::STATUS_ASSIGNED_TO_AUDITOR => '2. Dispatched',
                        Audit::STATUS_AUDIT_COMPLETED => '3. Audit Done',
                        Audit::STATUS_CAPA_SUBMITTED => '4. CAPA In Review',
                        Audit::STATUS_AUDIT_CLOSED => '5. Certified',
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
                    ->placeholder('Unassigned')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-user-group'),

                Tables\Columns\TextColumn::make('auditor.name')
                    ->label('Assigned Auditor')
                    ->placeholder('Pending Dispatch')
                    ->badge()
                    ->color(fn ($record) => $record->auditor_id ? 'success' : 'gray')
                    ->icon('heroicon-m-user'),

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
                // STEP 2: Assign & Dispatch Auditor
                Tables\Actions\Action::make('assignAuditor')
                    ->label('Assign & Dispatch')
                    ->icon('heroicon-m-user-plus')
                    ->color('primary')
                    ->visible(fn (Audit $record) => $record->workflow_status === Audit::STATUS_PENDING_ASSIGNMENT || ! $record->auditor_id)
                    ->form(fn (Audit $record): array => [
                        Forms\Components\Select::make('auditor_id')
                            ->label('Select Field / Peer Auditor')
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
                            ->helperText('Anti-Self-Audit Policy enforced: The designated Project Officer is excluded from selection.'),
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

                // STEP 5: Approve & Certify Audit
                Tables\Actions\Action::make('certifyAudit')
                    ->label('Approve & Certify')
                    ->icon('heroicon-m-shield-check')
                    ->color('success')
                    ->visible(fn (Audit $record) => in_array($record->workflow_status, [Audit::STATUS_AUDIT_COMPLETED, Audit::STATUS_CAPA_SUBMITTED]))
                    ->form([
                        Forms\Components\Textarea::make('closure_notes')
                            ->label('Certification & Closure Endorsement Notes')
                            ->default('Verification findings and remedial commitments validated. Audit certified.')
                            ->required(),
                    ])
                    ->action(function (Audit $record, array $data): void {
                        $record->update([
                            'workflow_status' => Audit::STATUS_AUDIT_CLOSED,
                            'certified_by_id' => auth()->id(),
                            'certified_at' => now(),
                            'closure_notes' => $data['closure_notes'],
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Audit Certified & Closed (Step 5 Completed)')
                            ->body("Audit {$record->audit_code} has been officially approved and closed.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('view')
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
            ]);
    }
}
