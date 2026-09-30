<?php

namespace App\Filament\Widgets;

use App\Models\AuditDimension;
use App\Services\DqaEngineService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class DimensionPerformanceChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Dimensional Compliance vs 85% Benchmark';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $filters = $this->filters;
        $engine = new DqaEngineService();
        $target = $engine->getGreenThreshold() * 100;

        $dimensions = ['Accuracy', 'Completeness', 'Consistency', 'Timeliness', 'Validity'];

        $query = AuditDimension::query()
            ->join('audits', 'audits.id', '=', 'audit_dimensions.audit_id');

        if (!empty($filters['project_id'])) {
            $query->where('audits.project_id', $filters['project_id']);
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

        $scores = [];
        $backgroundColors = [];
        $borderColors = [];

        foreach ($dimensions as $dim) {
            $rec = $records->get($dim);
            $checked = $rec ? (int) $rec->total_checked : 0;
            $compliant = $rec ? (int) $rec->total_compliant : 0;
            $score = $checked > 0 ? round(($compliant / $checked) * 100, 1) : 0.0;
            $status = $engine->computeStatus($checked > 0 ? ($compliant / $checked) : 0.0);

            $scores[] = $score;

            $backgroundColors[] = match($status) {
                'GREEN' => 'rgba(16, 185, 129, 0.75)',
                'YELLOW' => 'rgba(245, 158, 11, 0.75)',
                'ORANGE' => 'rgba(249, 115, 22, 0.75)',
                default => 'rgba(239, 68, 68, 0.75)',
            };

            $borderColors[] = match($status) {
                'GREEN' => '#059669',
                'YELLOW' => '#d97706',
                'ORANGE' => '#ea580c',
                default => '#dc2626',
            };
        }

        return [
            'datasets' => [
                [
                    'label' => 'Actual Compliance (%)',
                    'data' => $scores,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 2,
                    'borderRadius' => 6,
                ],
                [
                    'label' => "Target Benchmark ({$target}%)",
                    'data' => array_fill(0, count($dimensions), $target),
                    'type' => 'line',
                    'borderColor' => '#0284c7',
                    'borderDash' => [6, 4],
                    'borderWidth' => 2,
                    'pointRadius' => 0,
                    'fill' => false,
                ],
            ],
            'labels' => $dimensions,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'min' => 0,
                    'max' => 100,
                    'ticks' => [
                        'callback' => 'function(value) { return value + "%"; }',
                    ],
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.dataset.label + ": " + context.raw + "%"; }',
                    ],
                ],
            ],
        ];
    }
}
