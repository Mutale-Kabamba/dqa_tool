<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditResource\Pages;
use App\Models\Audit;
use App\Models\Project;
use App\Services\DqaEngineService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Field Audits';

    protected static ?string $navigationLabel = 'Audits';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // SECTION A: AUDIT METADATA
                Forms\Components\Section::make('Section A: Audit Metadata')
                    ->description('Site identification, auditor credentials, and temporal period')
                    ->schema([
                        Forms\Components\TextInput::make('audit_code')
                            ->label('Audit Code')
                            ->placeholder('e.g., AUD-001')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'AUD-' . str_pad((Audit::max('id') ?? 0) + 1, 3, '0', STR_PAD_LEFT))
                            ->maxLength(50),

                        Forms\Components\Select::make('project_id')
                            ->label('Project')
                            ->relationship('project', 'name')
                            ->options(Project::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('site_name')
                            ->label('Area / Facility / Site Name')
                            ->placeholder('e.g., Lusaka District - Site 1')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('auditor_name')
                            ->label('Auditor Name')
                            ->placeholder('e.g., J. Banda')
                            ->default(fn () => auth()->user()?->name ?? '')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('audit_date')
                            ->label('Audit Date')
                            ->required()
                            ->default(now())
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

                        Forms\Components\TextInput::make('facility_in_charge')
                            ->label('Facility In-Charge Name')
                            ->placeholder('e.g., Sister M. Phiri')
                            ->maxLength(255),

                        // Hidden/Auto-calculated temporal fields
                        Forms\Components\Hidden::make('period_month')->default(fn () => now()->month),
                        Forms\Components\Hidden::make('period_quarter')->default(fn () => now()->quarter),
                        Forms\Components\Hidden::make('period_year')->default(fn () => now()->year),
                        Forms\Components\Hidden::make('period_label')->default(fn () => now()->format('M-Y')),
                    ])->columns(3),

                // SECTION B: FIVE DIMENSION ENTRY TABLE
                Forms\Components\Section::make('Section B: Five Dimension Entry Table')
                    ->description('Input the number of records checked and found compliant for each standard data quality dimension')
                    ->schema([
                        Forms\Components\Repeater::make('dimensions')
                            ->relationship('dimensions')
                            ->label('Dimensions Assessment')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->default([
                                ['dimension_name' => 'Accuracy', 'checked_count' => 0, 'compliant_count' => 0, 'score_percentage' => 0.0, 'status' => 'RED'],
                                ['dimension_name' => 'Completeness', 'checked_count' => 0, 'compliant_count' => 0, 'score_percentage' => 0.0, 'status' => 'RED'],
                                ['dimension_name' => 'Consistency', 'checked_count' => 0, 'compliant_count' => 0, 'score_percentage' => 0.0, 'status' => 'RED'],
                                ['dimension_name' => 'Timeliness', 'checked_count' => 0, 'compliant_count' => 0, 'score_percentage' => 0.0, 'status' => 'RED'],
                                ['dimension_name' => 'Validity', 'checked_count' => 0, 'compliant_count' => 0, 'score_percentage' => 0.0, 'status' => 'RED'],
                            ])
                            ->schema([
                                Forms\Components\TextInput::make('dimension_name')
                                    ->label('Dimension')
                                    ->readOnly()
                                    ->extraInputAttributes(['class' => 'font-semibold text-gray-900 dark:text-gray-100']),

                                Forms\Components\TextInput::make('checked_count')
                                    ->label('Records Checked')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required()
                                    ->live(debounce: 400)
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        static::recalculateDimensionAndTotals($get, $set);
                                    }),

                                Forms\Components\TextInput::make('compliant_count')
                                    ->label('Records Compliant')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required()
                                    ->live(debounce: 400)
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        static::recalculateDimensionAndTotals($get, $set);
                                    }),

                                Forms\Components\TextInput::make('score_percentage')
                                    ->label('Score (%)')
                                    ->readOnly()
                                    ->formatStateUsing(fn ($state) => number_format(((float) $state) * 100, 1) . '%')
                                    ->extraInputAttributes(['class' => 'font-mono font-bold text-center']),

                                Forms\Components\TextInput::make('status')
                                    ->label('RAG Status')
                                    ->readOnly()
                                    ->extraInputAttributes(['class' => 'font-bold text-center uppercase']),
                            ])
                            ->columns(5)
                            ->columnSpanFull(),
                    ]),

                // SECTION C: AUTOMATED DIAGNOSTIC BANNER
                Forms\Components\Section::make('Section C: Automated Diagnostic Banner')
                    ->description('Real-time statistical evaluation against national RAG thresholds')
                    ->schema([
                        Forms\Components\Placeholder::make('diagnostic_banner')
                            ->label('')
                            ->content(function (Forms\Get $get) {
                                $checked = (int) ($get('overall_checked') ?? 0);
                                $compliant = (int) ($get('overall_compliant') ?? 0);
                                $score = (float) ($get('overall_score') ?? 0.0);
                                $status = $get('overall_status') ?? 'RED';
                                $priorities = $get('priority_areas') ?? 'None';

                                $statusColors = [
                                    'GREEN' => 'bg-emerald-50 text-emerald-800 border-emerald-400 dark:bg-emerald-950 dark:text-emerald-200',
                                    'YELLOW' => 'bg-amber-50 text-amber-800 border-amber-400 dark:bg-amber-950 dark:text-amber-200',
                                    'ORANGE' => 'bg-orange-50 text-orange-800 border-orange-400 dark:bg-orange-950 dark:text-orange-200',
                                    'RED' => 'bg-rose-50 text-rose-800 border-rose-400 dark:bg-rose-950 dark:text-rose-200',
                                ];

                                $badgeColors = [
                                    'GREEN' => 'bg-emerald-600 text-white',
                                    'YELLOW' => 'bg-amber-500 text-white',
                                    'ORANGE' => 'bg-orange-600 text-white',
                                    'RED' => 'bg-rose-600 text-white',
                                ];

                                $colorClass = $statusColors[$status] ?? $statusColors['RED'];
                                $badgeClass = $badgeColors[$status] ?? $badgeColors['RED'];
                                $pct = number_format($score * 100, 1);

                                // Check for escalation if any dimension is below 70%
                                $dimensions = $get('dimensions') ?? [];
                                $criticalDims = [];
                                foreach ($dimensions as $dim) {
                                    $dChecked = (int) ($dim['checked_count'] ?? 0);
                                    $dCompliant = (int) ($dim['compliant_count'] ?? 0);
                                    $dScore = $dChecked > 0 ? ($dCompliant / $dChecked) : 0.0;
                                    if ($dScore < 0.70 && !empty($dim['dimension_name'])) {
                                        $criticalDims[] = $dim['dimension_name'];
                                    }
                                }

                                $escalationHtml = '';
                                if (!empty($criticalDims)) {
                                    $escalationList = htmlspecialchars(implode(', ', $criticalDims));
                                    $escalationHtml = "
                                        <div class='mt-3 p-3 bg-red-100 border border-red-300 text-red-800 rounded-md text-sm font-medium flex items-center gap-2'>
                                            <svg class='w-5 h-5 flex-shrink-0 text-red-600' fill='currentColor' viewBox='0 0 20 20'>
                                                <path fill-rule='evenodd' d='M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z' clip-rule='evenodd' />
                                            </svg>
                                            <span><strong>Escalation Alert:</strong> Significant improvement required on: <strong>{$escalationList}</strong></span>
                                        </div>
                                    ";
                                }

                                $priorityBadges = '';
                                if ($priorities && $priorities !== 'None') {
                                    foreach (explode(',', $priorities) as $p) {
                                        $p = trim($p);
                                        if ($p) {
                                            $priorityBadges .= "<span class='px-2.5 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200 border border-orange-300'>{$p}</span> ";
                                        }
                                    }
                                } else {
                                    $priorityBadges = "<span class='text-sm text-gray-500 italic'>All dimensions meeting or exceeding 85% benchmark</span>";
                                }

                                return new HtmlString("
                                    <div class='rounded-xl border p-5 {$colorClass} shadow-sm transition-all'>
                                        <div class='grid grid-cols-1 md:grid-cols-4 gap-4 items-center'>
                                            <div>
                                                <div class='text-xs uppercase tracking-wider font-semibold opacity-75'>Total Checked</div>
                                                <div class='text-2xl font-bold font-mono'>{$checked}</div>
                                            </div>
                                            <div>
                                                <div class='text-xs uppercase tracking-wider font-semibold opacity-75'>Total Compliant</div>
                                                <div class='text-2xl font-bold font-mono'>{$compliant}</div>
                                            </div>
                                            <div>
                                                <div class='text-xs uppercase tracking-wider font-semibold opacity-75'>Overall Score</div>
                                                <div class='text-3xl font-extrabold font-mono tracking-tight'>{$pct}%</div>
                                            </div>
                                            <div>
                                                <div class='text-xs uppercase tracking-wider font-semibold opacity-75'>Audit Health Status</div>
                                                <span class='inline-block mt-1 px-3 py-1 rounded-full text-xs font-bold tracking-wider {$badgeClass} shadow-sm'>
                                                    {$status}
                                                </span>
                                            </div>
                                        </div>
                                        <div class='mt-4 pt-4 border-t border-current/20 flex flex-wrap items-center gap-2'>
                                            <span class='text-xs uppercase font-bold tracking-wider'>Priority Areas (&lt; 85%):</span>
                                            {$priorityBadges}
                                        </div>
                                        {$escalationHtml}
                                    </div>
                                ");
                            })
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('overall_checked')->default(0),
                        Forms\Components\Hidden::make('overall_compliant')->default(0),
                        Forms\Components\Hidden::make('overall_score')->default(0.0000),
                        Forms\Components\Hidden::make('overall_status')->default('RED'),
                        Forms\Components\Hidden::make('priority_areas')->default(''),
                    ]),

                // SECTION D: QUALITATIVE FEEDBACK & FIELD NOTES
                Forms\Components\Section::make('Section D: Qualitative Feedback & Field Notes')
                    ->description('Expert observations, operational root causes, and agreed corrective action plans')
                    ->schema([
                        Forms\Components\Textarea::make('root_cause_notes')
                            ->label('Operational Context & Root Causes')
                            ->placeholder('e.g., Severe staffing shortage during immunization campaign led to delayed register updates...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('recommendations')
                            ->label('Actionable Recommendations & Agreed Facility Next Steps')
                            ->placeholder('e.g., Facility in-charge agreed to assign a dedicated intake officer by February 15...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('audit_code')
                    ->label('Audit ID')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold),

                Tables\Columns\TextColumn::make('site_name')
                    ->label('Facility / Site')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('period_label')
                    ->label('Period')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('audit_date')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('overall_score')
                    ->label('Score')
                    ->formatStateUsing(fn ($state) => number_format(((float) $state) * 100, 1) . '%')
                    ->sortable()
                    ->alignCenter()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('overall_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'GREEN' => 'success',
                        'YELLOW' => 'warning',
                        'ORANGE' => 'danger',
                        'RED' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority_areas')
                    ->label('Priority Focus')
                    ->placeholder('None (All >= 85%)')
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->priority_areas),
            ])
            ->defaultSort('audit_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->relationship('project', 'name')
                    ->label('Filter by Project')
                    ->preload(),

                Tables\Filters\SelectFilter::make('overall_status')
                    ->label('Filter by Status')
                    ->options([
                        'GREEN' => 'Green (Good >= 85%)',
                        'YELLOW' => 'Yellow (70-84%)',
                        'ORANGE' => 'Orange (55-69%)',
                        'RED' => 'Red (< 55%)',
                    ]),

                Tables\Filters\SelectFilter::make('period_year')
                    ->label('Year')
                    ->options(fn () => Audit::query()->distinct()->pluck('period_year', 'period_year')->toArray()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->url(fn (Audit $record) => route('admin.audits.pdf', ['audit' => $record]))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Audit Dossier Overview')
                    ->schema([
                        Infolists\Components\TextEntry::make('audit_code')->label('Audit ID')->badge()->color('info'),
                        Infolists\Components\TextEntry::make('project.name')->label('Project Name')->weight(FontWeight::Bold),
                        Infolists\Components\TextEntry::make('site_name')->label('Facility / Site'),
                        Infolists\Components\TextEntry::make('auditor_name')->label('Auditor'),
                        Infolists\Components\TextEntry::make('audit_date')->label('Audit Date')->date('M j, Y'),
                        Infolists\Components\TextEntry::make('facility_in_charge')->label('Facility In-Charge')->placeholder('Not recorded'),
                        Infolists\Components\TextEntry::make('period_label')->label('Reporting Period')->badge(),
                        Infolists\Components\TextEntry::make('overall_score')
                            ->label('Overall Score')
                            ->formatStateUsing(fn ($state) => number_format(((float) $state) * 100, 1) . '%')
                            ->weight(FontWeight::Bold),
                        Infolists\Components\TextEntry::make('overall_status')
                            ->label('Overall Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'GREEN' => 'success',
                                'YELLOW' => 'warning',
                                'ORANGE' => 'danger',
                                'RED' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('priority_areas')
                            ->label('Priority Focus Areas')
                            ->placeholder('None (Optimal performance across all dimensions)')
                            ->columnSpanFull(),
                    ])->columns(3),

                Infolists\Components\Section::make('Five Dimension Performance Scorecard')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('dimensions')
                            ->schema([
                                Infolists\Components\TextEntry::make('dimension_name')->label('Dimension')->weight(FontWeight::Bold),
                                Infolists\Components\TextEntry::make('checked_count')->label('Checked'),
                                Infolists\Components\TextEntry::make('compliant_count')->label('Compliant'),
                                Infolists\Components\TextEntry::make('score_percentage')
                                    ->label('Compliance %')
                                    ->formatStateUsing(fn ($state) => number_format(((float) $state) * 100, 1) . '%')
                                    ->weight(FontWeight::Bold),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'GREEN' => 'success',
                                        'YELLOW' => 'warning',
                                        'ORANGE' => 'danger',
                                        'RED' => 'danger',
                                        default => 'gray',
                                    }),
                            ])
                            ->columns(5)
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Field Notes & Recommendations')
                    ->schema([
                        Infolists\Components\TextEntry::make('root_cause_notes')
                            ->label('Operational Context & Root Causes')
                            ->placeholder('No specific root causes noted')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('recommendations')
                            ->label('Actionable Recommendations & Agreed Next Steps')
                            ->placeholder('No recommendations entered')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function recalculateDimensionAndTotals(Forms\Get $get, Forms\Set $set): void
    {
        $checked = (int) ($get('checked_count') ?? 0);
        $compliant = (int) ($get('compliant_count') ?? 0);
        $score = $checked > 0 ? round($compliant / $checked, 4) : 0.0;

        $engine = new DqaEngineService();
        $status = $engine->computeStatus($score);

        $set('score_percentage', $score);
        $set('status', $status);

        // Access repeater items from root
        $dimensions = $get('../../dimensions') ?? [];
        $totalChecked = 0;
        $totalCompliant = 0;
        $priorities = [];

        foreach ($dimensions as $item) {
            $c = (int) ($item['checked_count'] ?? 0);
            $comp = (int) ($item['compliant_count'] ?? 0);
            $s = $c > 0 ? round($comp / $c, 4) : 0.0;

            if ($s < $engine->getGreenThreshold()) {
                if (!empty($item['dimension_name'])) {
                    $priorities[] = $item['dimension_name'];
                }
            }

            $totalChecked += $c;
            $totalCompliant += $comp;
        }

        $overallScore = $totalChecked > 0 ? round($totalCompliant / $totalChecked, 4) : 0.0;
        $overallStatus = $engine->computeStatus($overallScore);

        $set('../../overall_checked', $totalChecked);
        $set('../../overall_compliant', $totalCompliant);
        $set('../../overall_score', $overallScore);
        $set('../../overall_status', $overallStatus);
        $set('../../priority_areas', implode(', ', array_unique($priorities)));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAudits::route('/'),
            'create' => Pages\CreateAudit::route('/create'),
            'view' => Pages\ViewAudit::route('/{record}'),
            'edit' => Pages\EditAudit::route('/{record}/edit'),
        ];
    }
}
