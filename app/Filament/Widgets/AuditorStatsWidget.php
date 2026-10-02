<?php

namespace App\Filament\Widgets;

use App\Models\Audit;
use App\Services\DqaEngineService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AuditorStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();
        $filters = $this->filters;
        $query = Audit::query()->filtered($filters);

        if ($user && !$user->isMealOfficer()) {
            $query->where(function ($q) use ($user) {
                $q->where('auditor_id', $user->id)
                  ->orWhere('auditor_name', $user->name);
            });
        }

        $totalAssigned = (clone $query)->count();
        
        $pendingAudits = (clone $query)
            ->whereIn('workflow_status', [Audit::STATUS_ASSIGNED_TO_AUDITOR, Audit::STATUS_PENDING_ASSIGNMENT])
            ->count();

        $completedThisMonth = (clone $query)
            ->whereIn('workflow_status', [Audit::STATUS_AUDIT_COMPLETED, Audit::STATUS_CAPA_SUBMITTED, Audit::STATUS_AUDIT_CLOSED])
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        // Urgent audits: assigned and audit_date is within 3 days or in the past
        $urgentAudits = (clone $query)
            ->whereIn('workflow_status', [Audit::STATUS_ASSIGNED_TO_AUDITOR, Audit::STATUS_PENDING_ASSIGNMENT])
            ->where('audit_date', '<=', now()->addDays(3))
            ->count();

        return [
            Stat::make('Audits Awaiting Execution', number_format($pendingAudits))
                ->description('Pending verification queue')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color($pendingAudits > 0 ? 'warning' : 'success'),

            Stat::make('Audits Completed This Month', number_format($completedThisMonth))
                ->description('Monthly field audit throughput')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Urgent Audits (<= 3 Days)', number_format($urgentAudits))
                ->description('High-priority verification tasks')
                ->descriptionIcon('heroicon-m-clock')
                ->color($urgentAudits > 0 ? 'danger' : 'gray'),
        ];
    }
}
