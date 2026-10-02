<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CapaManagementResource\Pages;
use App\Models\Audit;
use App\Models\AuditActionItem;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CapaManagementResource extends Resource
{
    protected static ?string $model = AuditActionItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationGroup = 'Corrective Actions (CAPA)';

    protected static ?string $navigationLabel = 'CAPA Action Tracker';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->isMealOfficer() || $user->isProjectOfficer());
    }

    public static function getNavigationLabel(): string
    {
        $user = auth()->user();
        if ($user && $user->isProjectOfficer() && ! $user->isMealOfficer()) {
            return 'My Project CAPA Tasks';
        }

        return 'Global CAPA Tracker';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['audit.project', 'audit.projectOfficer']);
        $user = auth()->user();

        if ($user && ! $user->isMealOfficer() && $user->isProjectOfficer()) {
            $managedProjectIds = $user->managedProjects()->pluck('id')->toArray();
            $query->whereHas('audit', function ($q) use ($user, $managedProjectIds) {
                $q->where('project_officer_id', $user->id)
                  ->orWhereIn('project_id', $managedProjectIds);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('CAPA Remediation Task')
                    ->schema([
                        Forms\Components\Select::make('audit_id')
                            ->label('Associated Audit')
                            ->relationship('audit', 'audit_code')
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('dimension_name')
                            ->label('Data Quality Dimension')
                            ->options([
                                'Accuracy' => 'Accuracy',
                                'Completeness' => 'Completeness',
                                'Consistency' => 'Consistency',
                                'Timeliness' => 'Timeliness',
                                'Validity' => 'Validity',
                                'Cross-Cutting / General' => 'Cross-Cutting / General',
                            ])
                            ->required(),

                        Forms\Components\Select::make('root_cause_category')
                            ->label('Root Cause Category')
                            ->options([
                                'Staffing Shortage' => 'Staffing Shortage',
                                'Tools & Registers Stockout' => 'Tools & Registers Stockout',
                                'Training & Mentorship Need' => 'Training & Mentorship Need',
                                'EHR & Connectivity Glitch' => 'EHR & Connectivity Glitch',
                                'SOP Non-Adherence' => 'SOP Non-Adherence',
                                'Supervision Gap' => 'Supervision Gap',
                                'Other' => 'Other',
                            ])
                            ->default('Staffing Shortage')
                            ->required(),

                        Forms\Components\TextInput::make('issue_description')
                            ->label('Identified Discrepancy / Gap')
                            ->placeholder('e.g., Missing batch numbers in 20% of sampled entries')
                            ->required()
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('action_plan')
                            ->label('Agreed Remedial Action Plan')
                            ->placeholder('e.g., Restock registers and conduct on-site verification')
                            ->required()
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('responsible_person')
                            ->label('Person Responsible')
                            ->placeholder('e.g., Nurse In-Charge')
                            ->required(),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Target Resolution Date')
                            ->default(now()->addWeeks(2))
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('CAPA Lifecycle Status')
                            ->options([
                                'OPEN' => 'Open',
                                'IN_PROGRESS' => 'In Progress',
                                'RESOLVED' => 'Resolved',
                                'OVERDUE' => 'Overdue',
                            ])
                            ->default('OPEN')
                            ->required(),

                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('Remediation Notes / Verification Evidence')
                            ->placeholder('e.g., Action completed and verified on follow-up visit.')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('audit.audit_code')
                    ->label('Audit')
                    ->badge()
                    ->color('gray')
                    ->weight(FontWeight::Bold)
                    ->searchable(),

                Tables\Columns\TextColumn::make('audit.project.name')
                    ->label('Project')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(),

                Tables\Columns\TextColumn::make('audit.site_name')
                    ->label('Facility / Site')
                    ->searchable(),

                Tables\Columns\TextColumn::make('dimension_name')
                    ->label('Dimension')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('root_cause_category')
                    ->label('Root Cause')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('issue_description')
                    ->label('Identified Gap')
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->issue_description),

                Tables\Columns\TextColumn::make('action_plan')
                    ->label('Remedial Action')
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->action_plan),

                Tables\Columns\TextColumn::make('responsible_person')
                    ->label('Assigned Lead')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'RESOLVED' => 'success',
                        'IN_PROGRESS' => 'info',
                        'OVERDUE' => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
            ])
            ->defaultSort('due_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'OPEN' => 'Open',
                        'IN_PROGRESS' => 'In Progress',
                        'RESOLVED' => 'Resolved',
                        'OVERDUE' => 'Overdue',
                    ]),
                Tables\Filters\SelectFilter::make('dimension_name')
                    ->label('Dimension')
                    ->options([
                        'Accuracy' => 'Accuracy',
                        'Completeness' => 'Completeness',
                        'Consistency' => 'Consistency',
                        'Timeliness' => 'Timeliness',
                        'Validity' => 'Validity',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('quickUpdate')
                    ->label('Update')
                    ->icon('heroicon-m-pencil-square')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'OPEN' => 'Open',
                                'IN_PROGRESS' => 'In Progress',
                                'RESOLVED' => 'Resolved',
                                'OVERDUE' => 'Overdue',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('resolution_notes')
                            ->label('Remediation Notes / Verification Details')
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

                Tables\Actions\Action::make('viewAudit')
                    ->label('Dossier')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->url(fn (AuditActionItem $record): string => $record->audit ? AuditResource::getUrl('view', ['record' => $record->audit]) : '#'),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\CapaOverviewStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCapaManagement::route('/'),
            'create' => Pages\CreateCapaManagement::route('/create'),
            'edit' => Pages\EditCapaManagement::route('/{record}/edit'),
        ];
    }
}
