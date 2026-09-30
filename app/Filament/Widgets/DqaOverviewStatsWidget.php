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

        $overallScore = $totalChecked > 0 ? round($totalCompliant / $totalChecked, 4) : 0.0;
        $engine = new DqaEngineService();
        $overallStatus = $engine->computeStatus($overallScore);

        $criticalSitesCount = (clone $query)
            ->where(function ($q) {
                $q->whereIn('overall_status', ['ORANGE', 'RED'])
                    ->orWhereHas('dimensions', function ($dimQuery) {
                        $dimQuery->whereIn('status', ['ORANGE', 'RED']);
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

        return [
            Stat::make('Total Audits Completed', number_format($totalAudits))
                ->description('Field verification visits in scope')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('info'),

            Stat::make('Cumulative Records Audited', number_format($totalChecked))
                ->description(number_format($totalCompliant) . ' records found compliant')
                ->descriptionIcon('heroicon-m-document-magnifying-glass')
                ->color('primary'),

            Stat::make('Overall Portfolio Health', $scorePct)
                ->description("Status: {$overallStatus} (Pooled Average)")
                ->descriptionIcon('heroicon-m-shield-check')
                ->color($statusColor),

            Stat::make('Critical Sites for Follow-up', number_format($criticalSitesCount))
                ->description('Facilities flagged Orange or Red (< 70%)')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($criticalSitesCount > 0 ? 'danger' : 'success'),
        ];
    }
}
