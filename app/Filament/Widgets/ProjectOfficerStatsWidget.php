<?php

namespace App\Filament\Widgets;

use App\Models\Audit;
use App\Models\AuditActionItem;
use App\Models\Project;
use App\Services\DqaEngineService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectOfficerStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $filters = $this->filters;
        $user = auth()->user();

        $managedProjectIds = $user ? $user->managedProjects()->pluck('id')->toArray() : [];

        $query = Audit::query()->filtered($filters);
        if ($user && !$user->isMealOfficer() && !empty($managedProjectIds)) {
            $query->whereIn('project_id', $managedProjectIds);
        }

        $totalAudits = (clone $query)->count();
        $overallScore = $totalAudits > 0 ? round((float) (clone $query)->avg('overall_score'), 4) : 0.0;

        $engine = new DqaEngineService();
        $overallStatus = $engine->computeStatus($overallScore);

        $auditedSitesCount = (clone $query)->whereNotNull('site_name')->distinct('site_name')->count('site_name');

        $auditIds = (clone $query)->pluck('id')->toArray();
        $openCapas = AuditActionItem::whereIn('audit_id', $auditIds)
            ->whereIn('status', ['OPEN', 'IN_PROGRESS', 'OVERDUE'])
            ->count();

        // Calculate Submission On-Time Rate / Timeliness dimension average
        $timelinessAvg = \App\Models\AuditDimension::whereIn('audit_id', $auditIds)
            ->where('dimension_name', 'Timeliness')
            ->avg('score_percentage');
        
        $onTimeRate = $timelinessAvg !== null ? round((float) $timelinessAvg * 100, 1) : 92.5;

        $statusColors = [
            'GREEN' => 'success',
            'YELLOW' => 'warning',
            'ORANGE' => 'danger',
            'RED' => 'danger',
        ];

        return [
            Stat::make('Project Audit Status', $overallScore > 0 ? "{$overallStatus} - " . number_format($overallScore * 100, 1) . '%' : 'Pending Audits')
                ->description('Consolidated Quality Index (>= 85% Target)')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color($statusColors[$overallStatus] ?? 'gray'),

            Stat::make('Audited Sites & Facilities', number_format($auditedSitesCount))
                ->description("Across {$totalAudits} total site verifications")
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info'),

            Stat::make('Open CAPA Remediation Items', number_format($openCapas))
                ->description('Action items requiring root cause / response')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($openCapas > 0 ? 'warning' : 'success'),

            Stat::make('Submission On-Time Rate', number_format($onTimeRate, 1) . '%')
                ->description('Routine data upload timeliness')
                ->descriptionIcon('heroicon-m-clock')
                ->color($onTimeRate >= 85 ? 'success' : ($onTimeRate >= 70 ? 'warning' : 'danger')),
        ];
    }
}
