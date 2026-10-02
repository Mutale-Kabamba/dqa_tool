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

    protected static ?string $navigationGroup = 'Central Quality Operations';

    protected static ?string $navigationLabel = 'Master Audit Archive';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->isMealOfficer() || $user->isAuditor());
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isMealOfficer() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // STEPPER / WORKFLOW STATUS BANNER
                Forms\Components\Section::make('5-Step Operational Quality Assurance Workflow')
                    ->schema([
                        Forms\Components\Placeholder::make('workflow_stepper_banner')
                            ->label('')
                            ->content(function (Forms\Get $get, ?Audit $record) {
                                $status = $record?->workflow_status ?? $get('workflow_status') ?? Audit::STATUS_PENDING_ASSIGNMENT;

                                $steps = [
                                    1 => ['key' => Audit::STATUS_PENDING_ASSIGNMENT, 'name' => '1. Submission', 'desc' => 'Project Officer file upload & registration'],
                                    2 => ['key' => Audit::STATUS_ASSIGNED_TO_AUDITOR, 'name' => '2. Dispatch', 'desc' => 'MEAL Officer assigns peer auditor'],
                                    3 => ['key' => Audit::STATUS_AUDIT_COMPLETED, 'name' => '3. Audit Findings', 'desc' => 'Auditor tallies 5 dimensions & feedback'],
                                    4 => ['key' => Audit::STATUS_CAPA_SUBMITTED, 'name' => '4. CAPA Response', 'desc' => 'Project Officer root cause & remedial plan'],
                                    5 => ['key' => Audit::STATUS_AUDIT_CLOSED, 'name' => '5. Formal Certification', 'desc' => 'MEAL Officer closure & certified dossier'],
                                ];

                                $statusOrder = [
                                    Audit::STATUS_PENDING_ASSIGNMENT => 1,
                                    Audit::STATUS_ASSIGNED_TO_AUDITOR => 2,
                                    Audit::STATUS_AUDIT_COMPLETED => 3,
                                    Audit::STATUS_CAPA_SUBMITTED => 4,
                                    Audit::STATUS_AUDIT_CLOSED => 5,
                                ];

                                $currentStepIndex = $statusOrder[$status] ?? 1;

                                $stepsHtml = '';
                                foreach ($steps as $idx => $s) {
                                    $isCurrent = ($idx === $currentStepIndex);
                                    $isPassed = ($idx < $currentStepIndex);

                                    $stepBg = $isCurrent ? '#0284c7' : ($isPassed ? '#059669' : '#e2e8f0');
                                    $stepColor = ($isCurrent || $isPassed) ? '#ffffff' : '#64748b';
                                    $border = $isCurrent ? '2px solid #0369a1' : '1px solid #cbd5e1';
                                    $cardBg = $isCurrent ? '#f0f9ff' : ($isPassed ? '#f0fdf4' : '#ffffff');
                                    $titleColor = $isCurrent ? '#0369a1' : ($isPassed ? '#166534' : '#64748b');

                                    $icon = $isPassed ? '&check;' : $idx;

                                    $stepsHtml .= "
                                        <div style='flex: 1; min-width: 140px; background: {$cardBg}; border: {$border}; border-radius: 8px; padding: 10px 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);'>
                                            <div style='display: flex; align-items: center; gap: 8px;'>
                                                <span style='display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: {$stepBg}; color: {$stepColor}; font-size: 11px; font-weight: bold;'>{$icon}</span>
                                                <span style='font-size: 12px; font-weight: 700; color: {$titleColor};'>{$s['name']}</span>
                                            </div>
                                            <div style='font-size: 10px; color: #64748b; margin-top: 4px; line-height: 1.3;'>{$s['desc']}</div>
                                        </div>
                                    ";
                                }

                                return new HtmlString("
                                    <div style='display: flex; flex-wrap: wrap; gap: 10px; width: 100%;'>
                                        {$stepsHtml}
                                    </div>
                                ");
                            })
                            ->columnSpanFull(),
                    ]),

                // SECTION A: AUDIT METADATA, SUBMISSION & GOVERNANCE
                Forms\Components\Section::make('Section A: Audit Metadata, Submission & Governance')
                    ->description('Site identification, data file upload, auditor assignment, Project Officer attribution, and Anti-Self-Audit enforcement')
                    ->schema([
                        Forms\Components\TextInput::make('audit_code')
                            ->label('Audit Code')
                            ->placeholder('e.g., AUD-001')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'AUD-' . str_pad((Audit::max('id') ?? 0) + 1, 3, '0', STR_PAD_LEFT))
                            ->maxLength(50),

                        Forms\Components\Select::make('workflow_status')
                            ->label('Operational Workflow Stage')
                            ->options([
                                Audit::STATUS_PENDING_ASSIGNMENT => 'Step 1: Submission (Pending Dispatch)',
                                Audit::STATUS_ASSIGNED_TO_AUDITOR => 'Step 2: Dispatched to Auditor',
                                Audit::STATUS_AUDIT_COMPLETED => 'Step 3: Audit Findings Submitted',
                                Audit::STATUS_CAPA_SUBMITTED => 'Step 4: CAPA Action Plan Submitted',
                                Audit::STATUS_AUDIT_CLOSED => 'Step 5: Approved & Certified (Closed)',
                            ])
                            ->default(Audit::STATUS_PENDING_ASSIGNMENT)
                            ->required()
                            ->selectablePlaceholder(false)
                            ->native(false),

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
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state) {
                                    $project = Project::with('projectOfficer')->find($state);
                                    if ($project) {
                                        $set('project_officer_id', $project->project_officer_id);
                                        if ($project->projectOfficer) {
                                            $set('facility_in_charge', $project->projectOfficer->name);
                                        }
                                        $currentAuditor = $get('auditor_id');
                                        if ($currentAuditor && (int) $currentAuditor === (int) $project->project_officer_id) {
                                            $set('auditor_id', null);
                                            $set('auditor_name', '');
                                        }
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('site_name')
                            ->label('Area / Facility / Site Name')
                            ->placeholder('e.g., Lusaka District - Site 1')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('audit_date')
                            ->label('Audit / Verification Date')
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

                        Forms\Components\Select::make('project_officer_id')
                            ->label('Designated Project Officer')
                            ->relationship('projectOfficer', 'name', fn ($query) => $query->whereJsonContains('roles', \App\Models\User::ROLE_PROJECT_OFFICER)->orWhereJsonContains('roles', \App\Models\User::ROLE_MEAL_OFFICER))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $user = \App\Models\User::find($state);
                                    $set('facility_in_charge', $user?->name ?? '');
                                }
                            })
                            ->helperText('Designates the project officer managing this project and tracking CAPA.')
                            ->nullable(),

                        Forms\Components\TextInput::make('facility_in_charge')
                            ->label('Project Officer / Site Lead (Display)')
                            ->placeholder('e.g., J. Mwila (Project Officer)')
                            ->maxLength(255),

                        Forms\Components\Select::make('auditor_id')
                            ->label('Assigned Auditor / Peer Reviewer')
                            ->placeholder('Select assigned auditor...')
                            ->options(function (Forms\Get $get) {
                                $projectId = $get('project_id');
                                $excludedOfficerId = null;
                                if ($projectId) {
                                    $project = Project::find($projectId);
                                    $excludedOfficerId = $project?->project_officer_id;
                                }

                                return \App\Models\User::query()
                                    ->where('is_active', true)
                                    ->where(function ($q) {
                                        $q->whereJsonContains('roles', \App\Models\User::ROLE_AUDITOR)
                                          ->orWhereJsonContains('roles', \App\Models\User::ROLE_MEAL_OFFICER);
                                    })
                                    ->when($excludedOfficerId, fn ($q) => $q->where('id', '!=', $excludedOfficerId))
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $user = \App\Models\User::find($state);
                                    $set('auditor_name', $user?->name ?? '');
                                    $set('workflow_status', Audit::STATUS_ASSIGNED_TO_AUDITOR);
                                }
                            })
                            ->rules([
                                fn (Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $projectId = $get('project_id');
                                    if (! $projectId || ! $value) return;
                                    $project = Project::find($projectId);
                                    if ($project && (int) $project->project_officer_id === (int) $value) {
                                        $officer = \App\Models\User::find($value);
                                        $name = $officer ? $officer->name : "User #{$value}";
                                        $fail("Anti-Self-Audit Policy Violation: {$name} is the designated Project Officer for {$project->name} and cannot be assigned to audit their own project.");
                                    }
                                },
                            ])
                            ->helperText('Strict Anti-Self-Audit Policy: Project Officers are excluded from auditing their own projects.')
                            ->nullable(),

                        Forms\Components\TextInput::make('auditor_name')
                            ->label('Auditor Name (Display)')
                            ->placeholder('e.g., J. Banda')
                            ->default(fn () => auth()->user()?->name ?? '')
                            ->maxLength(255),

                        Forms\Components\FileUpload::make('data_file_path')
                            ->label('Data File Submission (Excel, CSV, or Scanned Register Summary)')
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
                            ->downloadable()
                            ->openable()
                            ->helperText('Step 1 Submission file uploaded by Project Officer for Auditor verification.')
                            ->columnSpanFull(),

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
                    ->description('Expert observations, operational root causes, strengths, discrepancies, and actionable recommendations')
                    ->schema([
                        Forms\Components\Textarea::make('root_cause_notes')
                            ->label('Operational Context & Root Causes')
                            ->placeholder('e.g., Severe staffing shortage during immunization campaign led to delayed register updates...')
                            ->rows(3),

                        Forms\Components\Textarea::make('strengths_notes')
                            ->label('Operational Strengths Observed')
                            ->placeholder('e.g., Diligent adherence to standard patient filing codes, clean storage, and prompt daily tallying...')
                            ->rows(3),

                        Forms\Components\Textarea::make('discrepancies_notes')
                            ->label('Discrepancies & Gaps Found')
                            ->placeholder('e.g., 14 register entries lacked client age categorization, and 5 weekly summaries had transcription gaps...')
                            ->rows(3),

                        Forms\Components\Textarea::make('recommendations')
                            ->label('Actionable Recommendations & Next Steps')
                            ->placeholder('e.g., Facility in-charge agreed to assign a dedicated intake officer by February 15...')
                            ->rows(3),
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

                Tables\Columns\TextColumn::make('workflow_status')
                    ->label('Workflow Stage')
                    ->badge()
                    ->color(fn (string $state): string | array => match ($state) {
                        Audit::STATUS_PENDING_ASSIGNMENT => 'warning',
                        Audit::STATUS_ASSIGNED_TO_AUDITOR => 'info',
                        Audit::STATUS_AUDIT_COMPLETED => \Filament\Support\Colors\Color::Purple,
                        Audit::STATUS_CAPA_SUBMITTED => \Filament\Support\Colors\Color::Orange,
                        Audit::STATUS_AUDIT_CLOSED => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Audit::STATUS_PENDING_ASSIGNMENT => 'Step 1: Submission Pending',
                        Audit::STATUS_ASSIGNED_TO_AUDITOR => 'Step 2: Dispatched',
                        Audit::STATUS_AUDIT_COMPLETED => 'Step 3: Audit Completed',
                        Audit::STATUS_CAPA_SUBMITTED => 'Step 4: CAPA Submitted',
                        Audit::STATUS_AUDIT_CLOSED => 'Step 5: Certified & Closed',
                        default => $state,
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        Audit::STATUS_PENDING_ASSIGNMENT => 'heroicon-m-clock',
                        Audit::STATUS_ASSIGNED_TO_AUDITOR => 'heroicon-m-paper-airplane',
                        Audit::STATUS_AUDIT_COMPLETED => 'heroicon-m-clipboard-document-check',
                        Audit::STATUS_CAPA_SUBMITTED => 'heroicon-m-arrow-path',
                        Audit::STATUS_AUDIT_CLOSED => 'heroicon-m-shield-check',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold),

                Tables\Columns\TextColumn::make('site_name')
                    ->label('Facility / Site')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('auditor.name')
                    ->label('Auditor')
                    ->default(fn ($record) => $record->auditor_name ?: 'Pending Dispatch')
                    ->badge()
                    ->color(fn ($record) => $record->auditor_id ? 'success' : 'gray')
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('projectOfficer.name')
                    ->label('Project Officer')
                    ->default(fn ($record) => $record->facility_in_charge)
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-user-group')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('data_file_path')
                    ->label('File')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => $record->data_file_path ? 'Data file submission attached' : 'No data file attached'),

                Tables\Columns\TextColumn::make('period_label')
                    ->label('Period')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('overall_score')
                    ->label('Score')
                    ->formatStateUsing(fn ($state) => number_format(((float) $state) * 100, 1) . '%')
                    ->sortable()
                    ->alignCenter()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('overall_status')
                    ->label('RAG')
                    ->badge()
                    ->color(fn (string $state): string | array => match ($state) {
                        'GREEN' => 'success',
                        'YELLOW' => 'warning',
                        'ORANGE' => \Filament\Support\Colors\Color::Orange,
                        'RED' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->defaultSort('audit_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('workflow_status')
                    ->label('Filter by Workflow Stage')
                    ->options([
                        Audit::STATUS_PENDING_ASSIGNMENT => 'Step 1: Submission (Pending Assignment)',
                        Audit::STATUS_ASSIGNED_TO_AUDITOR => 'Step 2: Dispatched to Auditor',
                        Audit::STATUS_AUDIT_COMPLETED => 'Step 3: Audit Completed',
                        Audit::STATUS_CAPA_SUBMITTED => 'Step 4: CAPA Submitted',
                        Audit::STATUS_AUDIT_CLOSED => 'Step 5: Approved & Certified',
                    ]),

                Tables\Filters\SelectFilter::make('project_id')
                    ->relationship('project', 'name')
                    ->label('Filter by Project')
                    ->preload(),

                Tables\Filters\SelectFilter::make('overall_status')
                    ->label('Filter by RAG')
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

                    // STEP 2 ACTION (MEAL OFFICER): Assign & Dispatch Auditor
                    Tables\Actions\Action::make('assignAuditorAction')
                        ->label('Assign & Dispatch Auditor')
                        ->icon('heroicon-m-user-plus')
                        ->color('primary')
                        ->visible(fn (Audit $record) => auth()->user()?->isMealOfficer() && $record->workflow_status === Audit::STATUS_PENDING_ASSIGNMENT)
                        ->form(fn (Audit $record): array => [
                            Forms\Components\Select::make('auditor_id')
                                ->label('Select Auditor / Peer Reviewer')
                                ->options(function () use ($record) {
                                    $excludedOfficerId = $record->project_officer_id ?? $record->project?->project_officer_id;

                                    return \App\Models\User::query()
                                        ->where('is_active', true)
                                        ->where(function ($q) {
                                            $q->whereJsonContains('roles', \App\Models\User::ROLE_AUDITOR)
                                              ->orWhereJsonContains('roles', \App\Models\User::ROLE_MEAL_OFFICER);
                                        })
                                        ->when($excludedOfficerId, fn ($q) => $q->where('id', '!=', $excludedOfficerId))
                                        ->pluck('name', 'id');
                                })
                                ->required()
                                ->helperText('Anti-Self-Audit Policy strictly excludes designated project staff.'),
                        ])
                        ->action(function (Audit $record, array $data): void {
                            $auditor = \App\Models\User::find($data['auditor_id']);
                            $record->update([
                                'auditor_id' => $data['auditor_id'],
                                'auditor_name' => $auditor?->name ?? $record->auditor_name,
                                'workflow_status' => Audit::STATUS_ASSIGNED_TO_AUDITOR,
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Auditor Dispatched (Step 2 Completed)')
                                ->body("Assigned {$auditor?->name} to audit {$record->audit_code}. Notification dispatched.")
                                ->success()
                                ->send();
                        }),

                    // STEP 3 ACTION (AUDITOR / MEAL): Submit Audit Findings
                    Tables\Actions\Action::make('submitAuditFindings')
                        ->label('Submit Audit Findings')
                        ->icon('heroicon-m-clipboard-document-check')
                        ->color('success')
                        ->visible(fn (Audit $record) => in_array($record->workflow_status, [Audit::STATUS_ASSIGNED_TO_AUDITOR, Audit::STATUS_PENDING_ASSIGNMENT]) && (auth()->user()?->isAuditor() || auth()->user()?->isMealOfficer()))
                        ->requiresConfirmation()
                        ->modalHeading('Submit Audit Findings (Step 3)')
                        ->modalDescription('Confirm that dimensional figures, operational context, strengths, and discrepancies have been recorded.')
                        ->action(function (Audit $record): void {
                            $record->update([
                                'workflow_status' => Audit::STATUS_AUDIT_COMPLETED,
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Audit Findings Submitted (Step 3 Completed)')
                                ->body("Audit {$record->audit_code} is now submitted. If CAPA is required, Project Officer has been alerted.")
                                ->success()
                                ->send();
                        }),

                    // STEP 4 ACTION (PROJECT OFFICER / MEAL): Submit CAPA Response
                    Tables\Actions\Action::make('submitCapaResponse')
                        ->label('Submit CAPA Response')
                        ->icon('heroicon-m-arrow-path')
                        ->color('warning')
                        ->visible(fn (Audit $record) => in_array($record->workflow_status, [Audit::STATUS_AUDIT_COMPLETED, Audit::STATUS_CAPA_SUBMITTED]) && ($record->needsCapa() || $record->actionItems()->exists()) && (auth()->user()?->isProjectOfficer() || auth()->user()?->isMealOfficer()))
                        ->form([
                            Forms\Components\Textarea::make('root_cause_notes')
                                ->label('Operational Root Cause Analysis')
                                ->placeholder('Identify primary system or staffing causes...')
                                ->required(),
                            Forms\Components\Textarea::make('recommendations')
                                ->label('Agreed Remedial Action Plan & Timeline')
                                ->placeholder('Detail corrective actions agreed with facility...')
                                ->required(),
                        ])
                        ->fillForm(fn (Audit $record): array => [
                            'root_cause_notes' => $record->root_cause_notes,
                            'recommendations' => $record->recommendations,
                        ])
                        ->action(function (Audit $record, array $data): void {
                            $record->update([
                                'root_cause_notes' => $data['root_cause_notes'],
                                'recommendations' => $data['recommendations'],
                                'workflow_status' => Audit::STATUS_CAPA_SUBMITTED,
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('CAPA Response Submitted (Step 4 Completed)')
                                ->body("Remedial action plan submitted for audit {$record->audit_code}. Pending MEAL certification.")
                                ->success()
                                ->send();
                        }),

                    // STEP 5 ACTION (MEAL OFFICER): Approve & Certify Audit
                    Tables\Actions\Action::make('certifyAndClose')
                        ->label('Approve & Certify Audit')
                        ->icon('heroicon-m-shield-check')
                        ->color('success')
                        ->visible(fn (Audit $record) => auth()->user()?->isMealOfficer() && in_array($record->workflow_status, [Audit::STATUS_AUDIT_COMPLETED, Audit::STATUS_CAPA_SUBMITTED]))
                        ->form([
                            Forms\Components\Textarea::make('closure_notes')
                                ->label('Certification & Quality Endorsement Notes')
                                ->placeholder('e.g., Remedial actions verified satisfactory. Audit officially certified and closed.')
                                ->default('Remedial actions and dimensional verification reviewed and approved by MEAL.')
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
                                ->body("Audit {$record->audit_code} is officially certified. Finalized PDF Dossier is ready.")
                                ->success()
                                ->send();
                        }),

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
                Infolists\Components\Section::make('Audit Dossier Overview & Governance')
                    ->schema([
                        Infolists\Components\TextEntry::make('audit_code')->label('Audit ID')->badge()->color('info'),
                        Infolists\Components\TextEntry::make('workflow_status')
                            ->label('Operational Stage')
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
                                Audit::STATUS_PENDING_ASSIGNMENT => 'Step 1: Submission (Pending Assignment)',
                                Audit::STATUS_ASSIGNED_TO_AUDITOR => 'Step 2: Dispatched to Auditor',
                                Audit::STATUS_AUDIT_COMPLETED => 'Step 3: Audit Completed',
                                Audit::STATUS_CAPA_SUBMITTED => 'Step 4: CAPA Plan Submitted',
                                Audit::STATUS_AUDIT_CLOSED => 'Step 5: Approved & Certified (Closed)',
                                default => $state,
                            }),
                        Infolists\Components\TextEntry::make('project.name')->label('Project Name')->weight(FontWeight::Bold),
                        Infolists\Components\TextEntry::make('site_name')->label('Facility / Site'),
                        Infolists\Components\TextEntry::make('auditor.name')
                            ->label('Assigned Auditor')
                            ->default(fn ($record) => $record->auditor_name ?: 'Pending Dispatch')
                            ->badge()
                            ->color(fn ($record) => $record->auditor_id ? 'success' : 'gray')
                            ->icon('heroicon-m-user'),
                        Infolists\Components\TextEntry::make('audit_date')->label('Audit Date')->date('M j, Y'),
                        Infolists\Components\TextEntry::make('projectOfficer.name')
                            ->label('Designated Project Officer')
                            ->default(fn ($record) => $record->facility_in_charge)
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-m-user-group')
                            ->placeholder('Not recorded'),
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
                        Infolists\Components\TextEntry::make('peer_governance_status')
                            ->label('Governance Policy Status')
                            ->state(fn ($record) => ($record->auditor_id && $record->project_officer_id && $record->auditor_id === $record->project_officer_id)
                                ? 'Violation: Self-Audit Detected'
                                : 'Peer-Audit & Anti-Self-Audit Policy Compliant')
                            ->badge()
                            ->color(fn ($record) => ($record->auditor_id && $record->project_officer_id && $record->auditor_id === $record->project_officer_id) ? 'danger' : 'success')
                            ->icon(fn ($record) => ($record->auditor_id && $record->project_officer_id && $record->auditor_id === $record->project_officer_id) ? 'heroicon-m-x-circle' : 'heroicon-m-check-badge'),
                        Infolists\Components\TextEntry::make('priority_areas')
                            ->label('Priority Focus Areas')
                            ->placeholder('None (Optimal performance across all dimensions)')
                            ->columnSpanFull(),
                    ])->columns(3),

                Infolists\Components\Section::make('Five Dimension Performance Scorecard')
                    ->schema([
                        Infolists\Components\ViewEntry::make('dimensions_table')
                            ->label('')
                            ->view('filament.infolists.components.dimension-scorecard-table')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Field Notes & Observations')
                    ->schema([
                        Infolists\Components\TextEntry::make('root_cause_notes')
                            ->label('Operational Context & Root Causes')
                            ->placeholder('No specific root causes noted')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('strengths_notes')
                            ->label('Operational Strengths Observed')
                            ->placeholder('No specific strengths recorded')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('discrepancies_notes')
                            ->label('Discrepancies & Quality Gaps Found')
                            ->placeholder('No discrepancies recorded')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('recommendations')
                            ->label('General Next Steps')
                            ->placeholder('No general recommendations entered')
                            ->columnSpanFull(),
                    ])->columns(2),

                Infolists\Components\Section::make('Corrective & Preventive Action (CAPA) Plan')
                    ->schema([
                        Infolists\Components\ViewEntry::make('capa_table')
                            ->label('')
                            ->view('filament.infolists.components.capa-plan-table')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Formal Certification & Closure Seal')
                    ->visible(fn ($record) => $record->workflow_status === Audit::STATUS_AUDIT_CLOSED || $record->certified_at !== null)
                    ->schema([
                        Infolists\Components\TextEntry::make('certifiedBy.name')
                            ->label('Certified & Approved By (MEAL Officer)')
                            ->badge()
                            ->color('success')
                            ->icon('heroicon-m-shield-check'),
                        Infolists\Components\TextEntry::make('certified_at')
                            ->label('Certification Timestamp')
                            ->dateTime('M j, Y H:i:s')
                            ->badge()
                            ->color('success'),
                        Infolists\Components\TextEntry::make('closure_notes')
                            ->label('Certification Endorsement Notes')
                            ->columnSpanFull(),
                    ])->columns(2),
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
