<?php

namespace App\Filament\Widgets;

use App\Models\AuditActionItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CapaOverviewStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();
        $query = AuditActionItem::query();

        if ($user && ! $user->isMealOfficer() && $user->isProjectOfficer()) {
            $managedProjectIds = $user->managedProjects()->pluck('id')->toArray();
            $query->whereHas('audit', function ($q) use ($user, $managedProjectIds) {
                $q->where('project_officer_id', $user->id)
                  ->orWhereIn('project_id', $managedProjectIds);
            });
        }

        $total = (clone $query)->count();
        $resolved = (clone $query)->where('status', 'RESOLVED')->count();
        $inProgress = (clone $query)->where('status', 'IN_PROGRESS')->count();
        $open = (clone $query)->where('status', 'OPEN')->count();
        
        $overdue = (clone $query)->where(function ($q) {
            $q->where('status', 'OVERDUE')
              ->orWhere(function ($subQ) {
                  $subQ->whereIn('status', ['OPEN', 'IN_PROGRESS'])
                       ->whereNotNull('due_date')
                       ->where('due_date', '<', now()->toDateString());
              });
        })->count();

        $resolutionRate = $total > 0 ? round(($resolved / $total) * 100, 1) : 100;

        return [
            Stat::make('Total Actions', number_format($total))
                ->description('Total identified remediation tasks')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),

            Stat::make('Resolved', number_format($resolved))
                ->description("{$resolutionRate}% resolved • Remediation closed")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('In Progress', number_format($inProgress))
                ->description('Active remedial implementation')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),

            Stat::make('Open / Overdue', number_format($open))
                ->description($overdue > 0 ? "{$overdue} overdue action items" : '0 overdue action items')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdue > 0 ? 'danger' : ($open > 0 ? 'warning' : 'success')),
        ];
    }
}
