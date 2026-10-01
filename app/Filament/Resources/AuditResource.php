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
                            ->label('Project Officer')
                            ->placeholder('e.g., J. Mwila (Project Officer)')
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
                                    ->rules([
                                        fn (Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $checked = (int) ($get('checked_count') ?? 0);
                                            if ((int) $value > $checked) {
                                                $fail("Compliant records ({$value}) cannot exceed records checked ({$checked}).");
                                            }
                                        },
                                    ])
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

                                $themes = [
                                    'GREEN' => [
                                        'container' => 'background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1.5px solid #86efac; color: #14532d;',
                                        'card'      => 'background: rgba(255, 255, 255, 0.85); border: 1px solid #bbf7d0; color: #14532d;',
                                        'badge'     => 'background-color: #15803d; color: #ffffff; border: 1px solid #166534; box-shadow: 0 2px 8px rgba(21, 128, 61, 0.35);',
                                        'label'     => 'color: #166534;',
                                    ],
                                    'YELLOW' => [
                                        'container' => 'background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%); border: 1.5px solid #fde047; color: #713f12;',
                                        'card'      => 'background: rgba(255, 255, 255, 0.85); border: 1px solid #fef08a; color: #713f12;',
                                        'badge'     => 'background-color: #d97706; color: #ffffff; border: 1px solid #b45309; box-shadow: 0 2px 8px rgba(217, 119, 6, 0.35);',
                                        'label'     => 'color: #854d0e;',
                                    ],
                                    'ORANGE' => [
                                        'container' => 'background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border: 1.5px solid #fdba74; color: #7c2d12;',
                                        'card'      => 'background: rgba(255, 255, 255, 0.85); border: 1px solid #fed7aa; color: #7c2d12;',
                                        'badge'     => 'background-color: #ea580c; color: #ffffff; border: 1px solid #c2410c; box-shadow: 0 2px 8px rgba(234, 88, 12, 0.35);',
                                        'label'     => 'color: #9a3412;',
                                    ],
                                    'RED' => [
                                        'container' => 'background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border: 1.5px solid #fca5a5; color: #7f1d1d;',
                                        'card'      => 'background: rgba(255, 255, 255, 0.85); border: 1px solid #fecdd3; color: #7f1d1d;',
                                        'badge'     => 'background-color: #dc2626; color: #ffffff; border: 1px solid #b91c1c; box-shadow: 0 2px 8px rgba(220, 38, 38, 0.35);',
                                        'label'     => 'color: #991b1b;',
                                    ],
                                ];

                                $t = $themes[$status] ?? $themes['RED'];
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
                                        <div style='margin-top: 14px; padding: 12px 16px; background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border: 1.5px solid #f43f5e; color: #881337; border-radius: 8px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 10px; box-shadow: 0 1px 3px rgba(244, 63, 94, 0.15);'>
                                            <span style='display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background-color: #e11d48; color: #ffffff; font-weight: bold; font-size: 14px; flex-shrink: 0;'>!</span>
                                            <div>
                                                <strong style='color: #9f1239; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;'>Escalation Alert:</strong>
                                                <span style='margin-left: 4px;'>Significant programmatic remediation required on: <strong>{$escalationList}</strong> (&lt; 70% compliance threshold).</span>
                                            </div>
                                        </div>
                                    ";
                                }

                                $priorityBadges = '';
                                if ($priorities && $priorities !== 'None') {
                                    foreach (explode(',', $priorities) as $p) {
                                        $p = trim($p);
                                        if ($p) {
                                            $priorityBadges .= "<span style='display: inline-block; padding: 3px 10px; font-size: 11px; font-weight: 700; border-radius: 9999px; background: #ffedd5; color: #9a3412; border: 1px solid #fdba74; margin-right: 6px;'>{$p}</span>";
                                        }
                                    }
                                } else {
                                    $priorityBadges = "<span style='font-size: 12px; color: #059669; font-style: italic; font-weight: 600;'>&check; All dimensions meeting or exceeding 85% national benchmark</span>";
                                }

                                return new HtmlString("
                                    <div style='border-radius: 12px; padding: 20px; {$t['container']} box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05); font-family: inherit;'>
                                        <!-- Top Summary Cards Grid -->
                                        <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 16px;'>
                                            
                                            <!-- Card 1: Total Checked -->
                                            <div style='border-radius: 10px; padding: 14px 16px; {$t['card']} box-shadow: 0 1px 3px rgba(0,0,0,0.04);'>
                                                <div style='font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; opacity: 0.85; {$t['label']}'>Total Checked</div>
                                                <div style='font-size: 26px; font-weight: 900; font-family: monospace; margin-top: 4px; line-height: 1.1;'>{$checked}</div>
                                                <div style='font-size: 11px; opacity: 0.7; margin-top: 2px;'>Sampled Records</div>
                                            </div>

                                            <!-- Card 2: Total Compliant -->
                                            <div style='border-radius: 10px; padding: 14px 16px; {$t['card']} box-shadow: 0 1px 3px rgba(0,0,0,0.04);'>
                                                <div style='font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; opacity: 0.85; {$t['label']}'>Total Compliant</div>
                                                <div style='font-size: 26px; font-weight: 900; font-family: monospace; margin-top: 4px; line-height: 1.1;'>{$compliant}</div>
                                                <div style='font-size: 11px; opacity: 0.7; margin-top: 2px;'>Conforming Records</div>
                                            </div>

                                            <!-- Card 3: Overall Score -->
                                            <div style='border-radius: 10px; padding: 14px 16px; {$t['card']} box-shadow: 0 1px 3px rgba(0,0,0,0.04);'>
                                                <div style='font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; opacity: 0.85; {$t['label']}'>Overall Quality Score</div>
                                                <div style='font-size: 28px; font-weight: 900; font-family: monospace; margin-top: 4px; line-height: 1.1;'>{$pct}%</div>
                                                <div style='font-size: 11px; opacity: 0.7; margin-top: 2px;'>Pooled Compliance</div>
                                            </div>

                                            <!-- Card 4: Health Status -->
                                            <div style='border-radius: 10px; padding: 14px 16px; {$t['card']} box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between;'>
                                                <div style='font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; opacity: 0.85; {$t['label']}'>Audit Health Status</div>
                                                <div>
                                                    <span style='display: inline-block; padding: 6px 16px; border-radius: 9999px; font-size: 13px; font-weight: 900; letter-spacing: 0.8px; text-transform: uppercase; {$t['badge']}'>
                                                        {$status}
                                                    </span>
                                                </div>
                                            </div>

                                        </div>

                                        <!-- Priority Areas Footer -->
                                        <div style='padding-top: 14px; border-top: 1px solid rgba(0, 0, 0, 0.12); display: flex; align-items: center; flex-wrap: wrap; gap: 8px;'>
                                            <span style='font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.6px; opacity: 0.9;'>Priority Action Focus (&lt; 85%):</span>
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
                    ->description('Expert observations, operational root causes, and general notes')
                    ->schema([
                        Forms\Components\Textarea::make('root_cause_notes')
                            ->label('Operational Context & Root Causes')
                            ->placeholder('e.g., Severe staffing shortage during immunization campaign led to delayed register updates...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('recommendations')
                            ->label('General Actionable Recommendations & Agreed Facility Next Steps')
                            ->placeholder('e.g., Facility in-charge agreed to assign a dedicated intake officer by February 15...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                // SECTION E: STRUCTURED ACTION PLAN & CAPA ITEMS
                Forms\Components\Section::make('Section E: Corrective & Preventive Action (CAPA) Plan')
                    ->description('Track specific remediation tasks, root cause categories, responsible officers, and target resolution dates')
                    ->schema([
                        Forms\Components\Repeater::make('actionItems')
                            ->relationship('actionItems')
                            ->label('Action Items / CAPA Tasks')
                            ->schema([
                                Forms\Components\Select::make('dimension_name')
                                    ->label('Dimension / Area')
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
                                    ->label('Identified Problem / Gap')
                                    ->placeholder('e.g. 17 register entries had missing batch numbers')
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('action_plan')
                                    ->label('Corrective Action to Take')
                                    ->placeholder('e.g. Conduct reconciliation and register restock by end of week')
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('responsible_person')
                                    ->label('Person Responsible')
                                    ->placeholder('e.g. Nurse In-Charge')
                                    ->required(),

                                Forms\Components\DatePicker::make('due_date')
                                    ->label('Target Date')
                                    ->default(now()->addWeeks(2))
                                    ->required(),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'OPEN' => 'Open',
                                        'IN_PROGRESS' => 'In Progress',
                                        'RESOLVED' => 'Resolved',
                                        'OVERDUE' => 'Overdue',
                                    ])
                                    ->default('OPEN')
                                    ->required(),

                                Forms\Components\TextInput::make('resolution_notes')
                                    ->label('Resolution / Follow-up Notes')
                                    ->placeholder('e.g. Verified resolved on Feb 12 follow-up visit')
                                    ->columnSpan(3),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->addActionLabel('+ Add CAPA Action Item')
                            ->columnSpanFull(),
                    ]),
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
                    ->color(fn (string $state): string | array => match ($state) {
                        'GREEN' => 'success',
                        'YELLOW' => 'warning',
                        'ORANGE' => \Filament\Support\Colors\Color::Orange,
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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\Action::make('pdf')
                        ->label('PDF Dossier')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('danger')
                        ->url(fn (Audit $record) => route('admin.audits.pdf', ['audit' => $record]))
                        ->openUrlInNewTab(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->tooltip('Actions')
                ->icon('heroicon-m-ellipsis-vertical'),
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
                        Infolists\Components\TextEntry::make('facility_in_charge')->label('Project Officer')->placeholder('Not recorded'),
                        Infolists\Components\TextEntry::make('period_label')->label('Reporting Period')->badge(),
                        Infolists\Components\TextEntry::make('overall_score')
                            ->label('Overall Score')
                            ->formatStateUsing(fn ($state) => number_format(((float) $state) * 100, 1) . '%')
                            ->weight(FontWeight::Bold),
                        Infolists\Components\TextEntry::make('overall_status')
                            ->label('Overall Status')
                            ->badge()
                            ->color(fn (string $state): string | array => match ($state) {
                                'GREEN' => 'success',
                                'YELLOW' => 'warning',
                                'ORANGE' => \Filament\Support\Colors\Color::Orange,
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
                                    ->color(fn (string $state): string | array => match ($state) {
                                        'GREEN' => 'success',
                                        'YELLOW' => 'warning',
                                        'ORANGE' => \Filament\Support\Colors\Color::Orange,
                                        'RED' => 'danger',
                                        default => 'gray',
                                    }),
                            ])
                            ->columns(5)
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Field Notes & Observations')
                    ->schema([
                        Infolists\Components\TextEntry::make('root_cause_notes')
                            ->label('Operational Context & Root Causes')
                            ->placeholder('No specific root causes noted')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('recommendations')
                            ->label('General Next Steps')
                            ->placeholder('No general recommendations entered')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Corrective & Preventive Action (CAPA) Plan')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('actionItems')
                            ->label('Remediation Action Items')
                            ->schema([
                                Infolists\Components\TextEntry::make('dimension_name')->label('Dimension / Area')->badge()->color('info'),
                                Infolists\Components\TextEntry::make('root_cause_category')->label('Root Cause')->weight(FontWeight::SemiBold),
                                Infolists\Components\TextEntry::make('issue_description')->label('Identified Problem')->columnSpan(2),
                                Infolists\Components\TextEntry::make('action_plan')->label('Corrective Action')->columnSpan(2),
                                Infolists\Components\TextEntry::make('responsible_person')->label('Responsible Person'),
                                Infolists\Components\TextEntry::make('due_date')->label('Due Date')->date('M j, Y'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'RESOLVED' => 'success',
                                        'IN_PROGRESS' => 'info',
                                        'OVERDUE' => 'danger',
                                        default => 'warning',
                                    }),
                                Infolists\Components\TextEntry::make('resolution_notes')->label('Resolution / Verification Notes')->placeholder('Pending resolution')->columnSpan(3),
                            ])
                            ->columns(4)
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
        $checkedCounts = [];
        $compliantCounts = [];
        $scores = [];
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

            $checkedCounts[] = $c;
            $compliantCounts[] = $comp;
            if ($c > 0) {
                $scores[] = $s;
            }
        }

        $uniqueRecordsChecked = !empty($checkedCounts) ? max($checkedCounts) : 0;
        $overallScore = !empty($scores) ? round(array_sum($scores) / count($scores), 4) : 0.0;
        $overallCompliant = (int) round($uniqueRecordsChecked * $overallScore);
        $overallStatus = $engine->computeStatus($overallScore);

        $set('../../overall_checked', $uniqueRecordsChecked);
        $set('../../overall_compliant', $overallCompliant);
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
