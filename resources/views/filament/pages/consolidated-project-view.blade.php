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
        </div>

        <!-- 4 Top KPI Metric Cards (1 Row) -->
        <style>
            .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
            @media (max-width: 1024px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
            @media (max-width: 640px)  { .kpi-grid { grid-template-columns: 1fr; } }

            .kpi-card {
                position: relative;
                background: #ffffff;
                border-radius: 14px;
                padding: 1.25rem 1.5rem 1.35rem;
                box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 14px rgba(0,0,0,.04);
                overflow: hidden;
                transition: transform .18s ease, box-shadow .18s ease;
                border: 1px solid rgba(0,0,0,.06);
            }
            .kpi-card::before {
                content: '';
                position: absolute;
                top: 0; left: 0; right: 0;
                height: 3px;
                border-radius: 14px 14px 0 0;
            }
            .kpi-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 6px 24px rgba(0,0,0,.10);
            }

            /* Accent colours per card */
            .kpi-card--indigo::before { background: linear-gradient(90deg, #6366f1, #818cf8); }
            .kpi-card--sky::before    { background: linear-gradient(90deg, #0ea5e9, #38bdf8); }
            .kpi-card--emerald::before{ background: linear-gradient(90deg, #10b981, #34d399); }
            .kpi-card--red::before    { background: linear-gradient(90deg, #ef4444, #f87171); }
            .kpi-card--gray::before   { background: linear-gradient(90deg, #94a3b8, #cbd5e1); }

            .kpi-card__header { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; margin-bottom: .9rem; }
            .kpi-card__label { font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; line-height: 1.4; }
            .kpi-card__icon  { flex-shrink: 0; width: 2.5rem; height: 2.5rem; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
            .kpi-card__icon svg { width: 1.25rem; height: 1.25rem; }

            .kpi-card__value { font-size: 2.4rem; font-weight: 900; line-height: 1; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
            .kpi-card__sub   { font-size: .72rem; margin-top: .35rem; opacity: .78; }

            /* Indigo */
            .kpi-card--indigo .kpi-card__label { color: #4338ca; }
            .kpi-card--indigo .kpi-card__icon  { background: linear-gradient(135deg, #eef2ff, #e0e7ff); color: #4f46e5; }
            .kpi-card--indigo .kpi-card__value { color: #1e1b4b; }
            .kpi-card--indigo .kpi-card__sub   { color: #6366f1; }
            /* Sky */
            .kpi-card--sky .kpi-card__label { color: #0369a1; }
            .kpi-card--sky .kpi-card__icon  { background: linear-gradient(135deg, #f0f9ff, #e0f2fe); color: #0284c7; }
            .kpi-card--sky .kpi-card__value { color: #082f49; }
            .kpi-card--sky .kpi-card__sub   { color: #0ea5e9; }
            /* Emerald */
            .kpi-card--emerald .kpi-card__label { color: #065f46; }
            .kpi-card--emerald .kpi-card__icon  { background: linear-gradient(135deg, #ecfdf5, #d1fae5); color: #059669; }
            .kpi-card--emerald .kpi-card__value { color: #064e3b; }
            .kpi-card--emerald .kpi-card__sub   { color: #10b981; }
            /* Red */
            .kpi-card--red .kpi-card__label { color: #991b1b; }
            .kpi-card--red .kpi-card__icon  { background: linear-gradient(135deg, #fef2f2, #fee2e2); color: #dc2626; }
            .kpi-card--red .kpi-card__value { color: #7f1d1d; }
            .kpi-card--red .kpi-card__sub   { color: #ef4444; }
            /* Gray (no critical) */
            .kpi-card--gray .kpi-card__label { color: #475569; }
            .kpi-card--gray .kpi-card__icon  { background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: #64748b; }
            .kpi-card--gray .kpi-card__value { color: #1e293b; }
            .kpi-card--gray .kpi-card__sub   { color: #64748b; }

            /* Dark mode */
            @media (prefers-color-scheme: dark) {
                .kpi-card { background: #1e2433; border-color: rgba(255,255,255,.07); }
                .kpi-card--indigo .kpi-card__icon  { background: linear-gradient(135deg, #1e1b4b, #312e81); color: #a5b4fc; }
                .kpi-card--indigo .kpi-card__value { color: #e0e7ff; }
                .kpi-card--sky .kpi-card__icon     { background: linear-gradient(135deg, #082f49, #0c4a6e); color: #7dd3fc; }
                .kpi-card--sky .kpi-card__value    { color: #e0f2fe; }
                .kpi-card--emerald .kpi-card__icon { background: linear-gradient(135deg, #052e16, #064e3b); color: #6ee7b7; }
                .kpi-card--emerald .kpi-card__value{ color: #d1fae5; }
                .kpi-card--red .kpi-card__icon     { background: linear-gradient(135deg, #450a0a, #7f1d1d); color: #fca5a5; }
                .kpi-card--red .kpi-card__value    { color: #fee2e2; }
                .kpi-card--gray .kpi-card__icon    { background: linear-gradient(135deg, #1e293b, #334155); color: #94a3b8; }
                .kpi-card--gray .kpi-card__value   { color: #e2e8f0; }
            }
        </style>

        <div class="kpi-grid">

            {{-- Card 1: Verified Visits --}}
            <div class="kpi-card kpi-card--indigo">
                <div class="kpi-card__header">
                    <span class="kpi-card__label">Verified Visits</span>
                    <div class="kpi-card__icon">
                        <x-heroicon-m-clipboard-document-check />
                    </div>
                </div>
                <div class="kpi-card__value">{{ number_format($metrics['total_visits']) }}</div>
                <div class="kpi-card__sub">Total conducted audit visits</div>
            </div>

            {{-- Card 2: Records Checked --}}
            <div class="kpi-card kpi-card--sky">
                <div class="kpi-card__header">
                    <span class="kpi-card__label">Records Checked</span>
                    <div class="kpi-card__icon">
                        <x-heroicon-m-document-magnifying-glass />
                    </div>
                </div>
                <div class="kpi-card__value">{{ number_format($metrics['total_checked']) }}</div>
                <div class="kpi-card__sub">Primary patient &amp; summary entries</div>
            </div>

            {{-- Card 3: Compliant Records --}}
            <div class="kpi-card kpi-card--emerald">
                <div class="kpi-card__header">
                    <span class="kpi-card__label">Compliant Records</span>
                    <div class="kpi-card__icon">
                        <x-heroicon-m-check-badge />
                    </div>
                </div>
                <div class="kpi-card__value">{{ number_format($metrics['total_compliant']) }}</div>
                <div class="kpi-card__sub">Fully validated records</div>
            </div>

            {{-- Card 4: Critical Sites (< 70%) --}}
            <div class="kpi-card {{ $metrics['critical_count'] > 0 ? 'kpi-card--red' : 'kpi-card--gray' }}">
                <div class="kpi-card__header">
                    <span class="kpi-card__label">Critical Sites (&lt; 70%)</span>
                    <div class="kpi-card__icon">
                        <x-heroicon-m-exclamation-triangle />
                    </div>
                </div>
                <div class="kpi-card__value">{{ number_format($metrics['critical_count']) }}</div>
                <div class="kpi-card__sub">
                    {{ $metrics['critical_count'] > 0 ? 'Facilities needing targeted support' : 'Zero critical facilities' }}
                </div>
            </div>

        </div>

        <!-- 5-Dimension Rollup and Priority Areas Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
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

        <!-- Space & Facility Longitudinal Quality Trajectory Row -->
        <div class="mt-8 mb-8">
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
    @endif

    <!-- Site-by-Site Table -->
    <div class="mt-6">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
