<?php

namespace App\Filament\Widgets;

use App\Models\AuditDimension;
use App\Services\DqaEngineService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class DimensionScorecardWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.dimension-scorecard';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $filters = $this->filters;
        $engine = new DqaEngineService();

        $standardDimensions = [
            'Accuracy' => [
                'definition' => 'Data correctly reflects the real-world person, event or object it describes.',
                'icon' => 'heroicon-m-check-badge',
            ],
            'Completeness' => [
                'definition' => 'All required fields/records are present with no missing forms or values.',
                'icon' => 'heroicon-m-document-check',
            ],
            'Consistency' => [
                'definition' => 'Data agrees with itself across forms, registers, systems or reporting periods.',
                'icon' => 'heroicon-m-arrows-right-left',
            ],
            'Timeliness' => [
                'definition' => 'Data is captured, updated and reported within expected timeframe.',
                'icon' => 'heroicon-m-clock',
            ],
            'Validity' => [
                'definition' => 'Data conforms to required format, type, range or defined code list.',
                'icon' => 'heroicon-m-finger-print',
            ],
        ];

        $query = AuditDimension::query()
            ->join('audits', 'audits.id', '=', 'audit_dimensions.audit_id');

        $user = auth()->user();
        if ($user && ! $user->isMealOfficer() && $user->isProjectOfficer() && empty($filters['project_id'])) {
            $managedProjectIds = $user->managedProjects()->pluck('id')->toArray();
            $query->whereIn('audits.project_id', $managedProjectIds);
        } elseif ($user && ! $user->isMealOfficer() && $user->isAuditor() && empty($filters['project_id'])) {
            $query->where('audits.auditor_id', $user->id);
        }

        if (!empty($filters['project_id'])) {
            $query->where('audits.project_id', $filters['project_id']);
        }
        if (!empty($filters['execution_status'])) {
            if ($filters['execution_status'] === 'ASSIGNED') {
                $query->whereIn('audits.workflow_status', [\App\Models\Audit::STATUS_ASSIGNED_TO_AUDITOR, \App\Models\Audit::STATUS_PENDING_ASSIGNMENT]);
            } elseif ($filters['execution_status'] === 'DONE') {
                $query->whereIn('audits.workflow_status', [\App\Models\Audit::STATUS_AUDIT_COMPLETED, \App\Models\Audit::STATUS_CAPA_SUBMITTED, \App\Models\Audit::STATUS_AUDIT_CLOSED]);
            }
        }
        if (!empty($filters['period_year'])) {
            $query->where('audits.period_year', $filters['period_year']);
        }
        if (!empty($filters['period_quarter'])) {
            $query->where('audits.period_quarter', $filters['period_quarter']);
        }
        if (!empty($filters['period_month'])) {
            $query->where('audits.period_month', $filters['period_month']);
        }

        $records = $query
            ->selectRaw('
                audit_dimensions.dimension_name,
                SUM(audit_dimensions.checked_count) as total_checked,
                SUM(audit_dimensions.compliant_count) as total_compliant
            ')
            ->groupBy('audit_dimensions.dimension_name')
            ->get()
            ->keyBy('dimension_name');

        $rows = [];
        $grandChecked = 0;
        $grandCompliant = 0;

        foreach ($standardDimensions as $name => $meta) {
            $rec = $records->get($name);
            $checked = $rec ? (int) $rec->total_checked : 0;
            $compliant = $rec ? (int) $rec->total_compliant : 0;
            $score = $checked > 0 ? round($compliant / $checked, 4) : 0.0;
            $status = $engine->computeStatus($score);

            $grandChecked += $checked;
            $grandCompliant += $compliant;

            $rows[] = [
                'name' => $name,
                'definition' => $meta['definition'],
                'icon' => $meta['icon'],
                'checked' => $checked,
                'compliant' => $compliant,
                'score' => $score,
                'score_pct' => number_format($score * 100, 1),
                'status' => $status,
            ];
        }

        $overallScore = $grandChecked > 0 ? round($grandCompliant / $grandChecked, 4) : 0.0;
        $overallStatus = $engine->computeStatus($overallScore);

        return [
            'dimensions' => $rows,
            'grand_checked' => $grandChecked,
            'grand_compliant' => $grandCompliant,
            'overall_score' => $overallScore,
            'overall_score_pct' => number_format($overallScore * 100, 1),
            'overall_status' => $overallStatus,
            'green_threshold' => $engine->getGreenThreshold(),
            'yellow_threshold' => $engine->getYellowThreshold(),
            'orange_threshold' => $engine->getOrangeThreshold(),
        ];
    }
}
