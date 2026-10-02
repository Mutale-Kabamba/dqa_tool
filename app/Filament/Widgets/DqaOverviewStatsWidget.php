<?php

namespace App\Filament\Widgets;

use App\Models\Audit;
use App\Services\DqaEngineService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DqaOverviewStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $filters = $this->filters;
        $query = Audit::query()->filtered($filters);

        $totalAudits = (clone $query)->count();
        $totalChecked = (int) (clone $query)->sum('overall_checked');
        $totalCompliant = (int) (clone $query)->sum('overall_compliant');

        $overallScore = $totalAudits > 0 ? round((float) (clone $query)->avg('overall_score'), 4) : 0.0;
        $engine = new DqaEngineService();
        $overallStatus = $engine->computeStatus($overallScore);

        $pendingDispatchCount = Audit::query()
            ->where(function ($q) {
                $q->where('workflow_status', Audit::STATUS_PENDING_ASSIGNMENT)
                  ->orWhereNull('auditor_id');
            })
            ->count();

        $openCapaCount = \App\Models\AuditActionItem::query()
            ->whereIn('status', ['OPEN', 'IN_PROGRESS', 'OVERDUE'])
            ->count();

        $overdueCapaCount = \App\Models\AuditActionItem::query()
            ->where(function ($q) {
                $q->where('status', 'OVERDUE')
                  ->orWhere(function ($q2) {
                      $q2->whereIn('status', ['OPEN', 'IN_PROGRESS'])
                         ->whereDate('due_date', '<', now());
                  });
            })
            ->count();

        $statusColors = [
            'GREEN' => 'success',
            'YELLOW' => 'warning',
            'ORANGE' => 'danger',
            'RED' => 'danger',
        ];

        $statusColor = $statusColors[$overallStatus] ?? 'gray';
        $scorePct = number_format($overallScore * 100, 1) . '%';

        $projectName = !empty($filters['project_id'])
            ? \App\Models\Project::find($filters['project_id'])?->name
            : null;
        $scopeLabel = $projectName ?: 'All Projects';

        return [
            Stat::make('Total Audits Conducted', number_format($totalAudits))
                ->description('Verification visits in timeframe')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('info'),

            Stat::make('Cumulative Records Audited', number_format($totalChecked))
                ->description(number_format($totalCompliant) . ' records pooled compliant')
                ->descriptionIcon('heroicon-m-document-magnifying-glass')
                ->color('primary'),

            Stat::make('Portfolio Quality Index', $scorePct)
                ->description("Composite Status: {$overallStatus} ({$scopeLabel})")
                ->descriptionIcon('heroicon-m-shield-check')
                ->color($statusColor),

            Stat::make('Pending Dispatch', number_format($pendingDispatchCount))
                ->description('Awaiting auditor assignment')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color($pendingDispatchCount > 0 ? 'warning' : 'success'),

            Stat::make('Active CAPA Actions', number_format($openCapaCount))
                ->description($overdueCapaCount > 0 ? "{$overdueCapaCount} Overdue items requiring action" : 'All remediations on schedule')
                ->descriptionIcon('heroicon-m-arrow-path-rounded-square')
                ->color($overdueCapaCount > 0 ? 'danger' : ($openCapaCount > 0 ? 'warning' : 'success')),
        ];
    }
}
