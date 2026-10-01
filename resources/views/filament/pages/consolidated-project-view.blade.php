<x-filament-panels::page>
    <!-- Filter Bar -->
    <div class="mb-6">
        {{ $this->form }}
    </div>

    @php
        $metrics = $this->consolidatedMetrics;
    @endphp

    @if (!empty($metrics['project']))
        @php
            $statusStyles = [
                'GREEN' => [
                    'banner' => 'background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;',
                    'badge' => 'background-color: #10b981; color: #ffffff; border: 1px solid #059669;',
                ],
                'YELLOW' => [
                    'banner' => 'background-color: #fffbeb; color: #92400e; border: 1px solid #fde68a;',
                    'badge' => 'background-color: #f59e0b; color: #ffffff; border: 1px solid #d97706;',
                ],
                'ORANGE' => [
                    'banner' => 'background-color: #fff7ed; color: #9a3412; border: 1px solid #fed7aa;',
                    'badge' => 'background-color: #f97316; color: #ffffff; border: 1px solid #ea580c;',
                ],
                'RED' => [
                    'banner' => 'background-color: #fef2f2; color: #991b1b; border: 1px solid #fecdd3;',
                    'badge' => 'background-color: #ef4444; color: #ffffff; border: 1px solid #dc2626;',
                ],
            ];
            $status = $metrics['overall_status'];
            $theme = $statusStyles[$status] ?? $statusStyles['RED'];
        @endphp

        <!-- Project Executive Summary Banner -->
        <div class="rounded-xl border p-6 shadow-sm mb-6 transition-all" style="{{ $theme['banner'] }}">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider" style="background-color: rgba(0,0,0,0.08);">
                            {{ $metrics['project']->code }}
                        </span>
                        <h2 class="text-2xl font-black tracking-tight">{{ $metrics['project']->name }}</h2>
                    </div>
                    <p class="text-xs opacity-80 mt-1 max-w-xl">
                        {{ $metrics['project']->description ?? 'Consolidated program performance overview for audited field sites.' }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <div class="text-xs uppercase font-semibold opacity-75">Consolidated Status</div>
                        <div class="text-3xl font-extrabold font-mono">{{ $metrics['overall_score_pct'] }}%</div>
                    </div>
                    <span class="px-4 py-2 rounded-xl text-sm font-black tracking-wider uppercase shadow-sm" style="{{ $theme['badge'] }}">
                        {{ $status }}
                    </span>
                </div>
            </div>

            <!-- Key Metric Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-current/20">
                <div>
                    <div class="text-xs uppercase tracking-wider font-semibold opacity-75">Verified Visits</div>
                    <div class="text-2xl font-bold font-mono">{{ number_format($metrics['total_visits']) }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider font-semibold opacity-75">Records Checked</div>
                    <div class="text-2xl font-bold font-mono">{{ number_format($metrics['total_checked']) }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider font-semibold opacity-75">Compliant Records</div>
                    <div class="text-2xl font-bold font-mono">{{ number_format($metrics['total_compliant']) }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider font-semibold opacity-75">Critical Sites (&lt; 70%)</div>
                    <div class="text-2xl font-bold font-mono {{ $metrics['critical_count'] > 0 ? 'text-red-600 dark:text-red-400' : '' }}">
                        {{ number_format($metrics['critical_count']) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- 5-Dimension Consolidated Rollup -->
            <div class="lg:col-span-2">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-chart-bar class="w-5 h-5 text-primary-500" />
                            <span>Project Dimensional Rollup (Pooled Micro-Averages)</span>
                        </div>
                    </x-slot>

                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                        <table class="w-full text-left text-sm divide-y divide-gray-200 dark:divide-gray-800">
                            <thead class="bg-gray-50 dark:bg-gray-900/60 text-xs uppercase font-semibold text-gray-600 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">Dimension</th>
                                    <th class="px-4 py-3 text-right">Checked</th>
                                    <th class="px-4 py-3 text-right">Compliant</th>
                                    <th class="px-4 py-3 text-center">Score %</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 bg-white dark:bg-gray-900">
                                @foreach ($metrics['dimensions'] as $dim)
                                    @php
                                        $badgeStyle = match($dim['status']) {
                                            'GREEN' => 'background-color: #dcfce7; color: #166534; border: 1px solid #86efac;',
                                            'YELLOW' => 'background-color: #fef3c7; color: #92400e; border: 1px solid #fcd34d;',
                                            'ORANGE' => 'background-color: #ffedd5; color: #9a3412; border: 1px solid #fdba74;',
                                            default => 'background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;',
                                        };
                                    @endphp
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition">
                                        <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $dim['name'] }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono text-gray-700 dark:text-gray-300">
                                            {{ number_format($dim['checked']) }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono font-medium text-gray-900 dark:text-gray-100">
                                            {{ number_format($dim['compliant']) }}
                                        </td>
                                        <td class="px-4 py-3 text-center font-mono font-bold text-gray-900 dark:text-gray-100">
                                            {{ $dim['score_pct'] }}%
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold tracking-wider shadow-sm uppercase" style="{{ $badgeStyle }}">
                                                {{ $dim['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            </div>

            <!-- Recurring Priority Areas Card -->
            <div class="lg:col-span-1">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-exclamation-circle class="w-5 h-5 text-amber-500" />
                            <span>Recurring Priority Areas</span>
                        </div>
                    </x-slot>
                    <x-slot name="description">
                        Dimensions requiring systemic programmatic follow-up
                    </x-slot>

                    @if (!empty($metrics['recurring_priorities']))
                        <div class="space-y-3">
                            @foreach ($metrics['recurring_priorities'] as $priority)
                                <div class="p-3 bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-amber-900 dark:text-amber-200 text-sm">
                                            {{ $priority['dimension'] }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-200/80 dark:bg-amber-900 text-amber-900 dark:text-amber-100">
                                            {{ $priority['percentage'] }}% of visits
                                        </span>
                                    </div>
                                    <div class="text-xs text-amber-700 dark:text-amber-400 mt-1">
                                        Flagged in <strong>{{ $priority['count'] }}</strong> out of <strong>{{ $priority['total'] }}</strong> audit visits
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 rounded-lg text-sm flex items-center gap-2" style="background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;">
                            <x-heroicon-m-check-circle class="w-5 h-5 flex-shrink-0" style="color: #059669;" />
                            <span>No recurring priority areas flagged in this timeframe (all dimensions &ge; 85%).</span>
                        </div>
                    @endif
                </x-filament::section>
            </div>
        </div>

        <!-- CAPA & Longitudinal Trajectory Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- CAPA Remediation Status -->
            <div class="lg:col-span-1">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-clipboard-document-list class="w-5 h-5 text-indigo-500" />
                            <span>CAPA Action Plan Health</span>
                        </div>
                    </x-slot>
                    <x-slot name="description">
                        Status of remediation tasks across sites
                    </x-slot>

                    @php
                        $capa = $metrics['capa_summary'] ?? ['total' => 0, 'open' => 0, 'in_progress' => 0, 'resolved' => 0, 'overdue' => 0];
                    @endphp

                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="text-xs uppercase text-gray-500 font-semibold">Total Actions</div>
                            <div class="text-xl font-bold font-mono">{{ $capa['total'] }}</div>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 rounded-lg border border-emerald-200 dark:border-emerald-800">
                            <div class="text-xs uppercase text-emerald-700 dark:text-emerald-300 font-semibold">Resolved</div>
                            <div class="text-xl font-bold font-mono text-emerald-700 dark:text-emerald-300">{{ $capa['resolved'] }}</div>
                        </div>
                        <div class="p-3 bg-sky-50 dark:bg-sky-950/40 rounded-lg border border-sky-200 dark:border-sky-800">
                            <div class="text-xs uppercase text-sky-700 dark:text-sky-300 font-semibold">In Progress</div>
                            <div class="text-xl font-bold font-mono text-sky-700 dark:text-sky-300">{{ $capa['in_progress'] }}</div>
                        </div>
                        <div class="p-3 bg-amber-50 dark:bg-amber-950/40 rounded-lg border border-amber-200 dark:border-amber-800">
                            <div class="text-xs uppercase text-amber-700 dark:text-amber-300 font-semibold">Open / Overdue</div>
                            <div class="text-xl font-bold font-mono {{ $capa['overdue'] > 0 ? 'text-red-600' : 'text-amber-700 dark:text-amber-300' }}">
                                {{ $capa['open'] }} <span class="text-xs font-normal text-red-500">({{ $capa['overdue'] }} overdue)</span>
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            <!-- Facility Longitudinal Quality Trajectory -->
            <div class="lg:col-span-2">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-arrow-trending-up class="w-5 h-5 text-emerald-500" />
                            <span>Facility Longitudinal Quality Trajectory (Audit Trends)</span>
                        </div>
                    </x-slot>
                    <x-slot name="description">
                        Track progress trajectory and historical compliance evolution per health facility
                    </x-slot>

                    @if (!empty($metrics['facility_trajectories']))
                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                            <table class="w-full text-left text-sm divide-y divide-gray-200 dark:divide-gray-800">
                                <thead class="bg-gray-50 dark:bg-gray-900/60 text-xs uppercase font-semibold text-gray-600 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3">Facility / Site</th>
                                        <th class="px-4 py-3 text-center">Audits Conducted</th>
                                        <th class="px-4 py-3 text-center">Latest Score</th>
                                        <th class="px-4 py-3 text-center">Trajectory Trend</th>
                                        <th class="px-4 py-3">Audit History Timeline</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 bg-white dark:bg-gray-900">
                                    @foreach ($metrics['facility_trajectories'] as $traj)
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition">
                                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $traj['site_name'] }}
                                            </td>
                                            <td class="px-4 py-3 text-center font-mono text-gray-600 dark:text-gray-400">
                                                {{ $traj['audit_count'] }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="font-bold font-mono text-gray-900 dark:text-gray-100">{{ $traj['latest_score_pct'] }}%</span>
                                                <span class="text-xs text-gray-500 ml-1">({{ $traj['latest_period'] }})</span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @if ($traj['trend'] === 'improving')
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                                                        <span>&uarr; +{{ $traj['delta'] }}%</span>
                                                        <span class="font-normal text-[10px]">Improving</span>
                                                    </span>
                                                @elseif ($traj['trend'] === 'deteriorating')
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200">
                                                        <span>&darr; {{ $traj['delta'] }}%</span>
                                                        <span class="font-normal text-[10px]">Deteriorating</span>
                                                    </span>
                                                @elseif ($traj['trend'] === 'stable')
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                                        <span>&rarr; Stable</span>
                                                    </span>
                                                @else
                                                    <span class="text-xs text-gray-400 italic">Baseline (1 Visit)</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    @foreach ($traj['history'] as $h)
                                                        @php
                                                            $dotBg = match($h['status']) {
                                                                'GREEN' => 'bg-emerald-600 text-white',
                                                                'YELLOW' => 'bg-amber-500 text-white',
                                                                'ORANGE' => 'bg-orange-600 text-white',
                                                                default => 'bg-rose-600 text-white',
                                                            };
                                                        @endphp
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-mono {{ $dotBg }}" title="{{ $h['audit_code'] }}: {{ $h['period'] }} - {{ $h['score_pct'] }}%">
                                                            <span>{{ $h['period'] }}</span>
                                                            <span class="font-bold">{{ $h['score_pct'] }}%</span>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-filament::section>
            </div>
        </div>
    @endif

    <!-- Site-by-Site Table -->
    <div class="mt-6">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
