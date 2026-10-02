<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditSubmissionResource\Pages;
use App\Models\Audit;
use App\Models\Project;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditSubmissionResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationGroup = 'Central Quality Operations';

    protected static ?string $navigationLabel = 'Incoming Audit Submissions';

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
            return 'My Data Submissions';
        }

        return 'Incoming Audit Submissions';
    }

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();
        if ($user && $user->isProjectOfficer() && ! $user->isMealOfficer()) {
            return 'My Project Operations';
        }

        return 'Central Quality Operations';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['project', 'projectOfficer', 'auditor']);
        $user = auth()->user();

        if ($user && ! $user->isMealOfficer() && $user->isProjectOfficer()) {
            $managedIds = $user->managedProjects()->pluck('id')->toArray();
            $query->where(function ($q) use ($user, $managedIds) {
                $q->where('project_officer_id', $user->id)
                  ->orWhereIn('project_id', $managedIds);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Step 1: Data File Submission')
                    ->description('Upload routine monthly/quarterly data files and register summaries for DQA review')
                    ->schema([
                        Forms\Components\TextInput::make('audit_code')
                            ->label('Submission ID')
                            ->default(fn () => 'SUB-' . str_pad((Audit::max('id') ?? 0) + 1, 3, '0', STR_PAD_LEFT))
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\Select::make('project_id')
                            ->label('Project')
                            ->relationship('project', 'name')
                            ->options(function () {
                                $user = auth()->user();
                                $query = Project::where('is_active', true);
                                if ($user && ! $user->isMealOfficer() && $user->isProjectOfficer()) {
                                    $query->where('project_officer_id', $user->id);
                                }
                                return $query->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $project = Project::find($state);
                                    if ($project) {
                                        $set('project_officer_id', $project->project_officer_id);
                                        if ($project->projectOfficer) {
                                            $set('facility_in_charge', $project->projectOfficer->name);
                                        }
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('site_name')
                            ->label('Facility / Area / Site')
                            ->placeholder('e.g., Lusaka District - Chilenje Hospital')
                            ->required(),

                        Forms\Components\DatePicker::make('audit_date')
                            ->label('Reporting / Verification Date')
                            ->default(now())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $date = Carbon::parse($state);
                                    $set('period_month', $date->month);
                                    $set('period_quarter', $date->quarter);
                                    $set('period_year', $date->year);
                                    $set('period_label', $date->format('M-Y'));
                                }
                            }),

                        Forms\Components\FileUpload::make('data_file_path')
                            ->label('Data Source Submission (Excel, CSV, or Scanned Summary)')
                            ->disk('public')
                            ->directory('audit-submissions')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                                'text/plain',
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ])
                            ->required()
                            ->downloadable()
                            ->openable()
                            ->helperText('Upload primary register summary, Excel dataset, or CSV for independent auditor verification.')
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('workflow_status')->default(Audit::STATUS_PENDING_ASSIGNMENT),
                        Forms\Components\Hidden::make('project_officer_id')->default(fn () => auth()->id()),
                        Forms\Components\Hidden::make('facility_in_charge')->default(fn () => auth()->user()?->name ?? ''),
                        Forms\Components\Hidden::make('period_month')->default(fn () => now()->month),
                        Forms\Components\Hidden::make('period_quarter')->default(fn () => now()->quarter),
                        Forms\Components\Hidden::make('period_year')->default(fn () => now()->year),
                        Forms\Components\Hidden::make('period_label')->default(fn () => now()->format('M-Y')),
                    ])->columns(2),
            ]);
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

                Tables\Columns\TextColumn::make('workflow_status')
                    ->label('Submission Status')
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
                        Audit::STATUS_PENDING_ASSIGNMENT => 'Pending Assignment',
                        Audit::STATUS_ASSIGNED_TO_AUDITOR => 'In Audit (Dispatched)',
                        Audit::STATUS_AUDIT_COMPLETED => 'Audit Completed',
                        Audit::STATUS_CAPA_SUBMITTED => 'CAPA In Review',
                        Audit::STATUS_AUDIT_CLOSED => 'Certified & Closed',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(),

                Tables\Columns\TextColumn::make('site_name')
                    ->label('Facility / Site')
                    ->searchable(),

                Tables\Columns\TextColumn::make('period_label')
                    ->label('Period')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('data_file_path')
                    ->label('File')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('auditor.name')
                    ->label('Assigned Auditor')
                    ->placeholder('Pending Dispatch')
                    ->badge()
                    ->color(fn ($record) => $record->auditor_id ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('overall_score')
                    ->label('Quality Score')
                    ->formatStateUsing(fn ($state) => $state > 0 ? number_format(((float) $state) * 100, 1) . '%' : 'Pending Tally')
                    ->alignCenter(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('downloadFile')
                    ->label('Download Data')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->visible(fn (Audit $record) => ! empty($record->data_file_path))
                    ->url(fn (Audit $record) => asset('storage/' . $record->data_file_path))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('viewDossier')
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
            'index' => Pages\ListAuditSubmissions::route('/'),
            'create' => Pages\CreateAuditSubmission::route('/create'),
        ];
    }
}
