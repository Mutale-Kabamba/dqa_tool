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
            $statusColors = [
                'GREEN' => 'bg-emerald-50 text-emerald-800 border-emerald-400 dark:bg-emerald-950 dark:text-emerald-200',
                'YELLOW' => 'bg-amber-50 text-amber-800 border-amber-400 dark:bg-amber-950 dark:text-amber-200',
                'ORANGE' => 'bg-orange-50 text-orange-800 border-orange-400 dark:bg-orange-950 dark:text-orange-200',
                'RED' => 'bg-rose-50 text-rose-800 border-rose-400 dark:bg-rose-950 dark:text-rose-200',
            ];
            $badgeColors = [
                'GREEN' => 'bg-emerald-600 text-white',
                'YELLOW' => 'bg-amber-500 text-white',
                'ORANGE' => 'bg-orange-600 text-white',
                'RED' => 'bg-rose-600 text-white',
            ];
            $status = $metrics['overall_status'];
            $colorClass = $statusColors[$status] ?? $statusColors['RED'];
            $badgeClass = $badgeColors[$status] ?? $badgeColors['RED'];
        @endphp

        <!-- Project Executive Summary Banner -->
        <div class="rounded-xl border p-6 {{ $colorClass }} shadow-sm mb-6 transition-all">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-black/10 dark:bg-white/10">
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
                    <span class="px-4 py-2 rounded-xl text-sm font-black tracking-wider {{ $badgeClass }} shadow-sm">
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
                                            'GREEN' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300',
                                            'YELLOW' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/60 dark:text-amber-300',
                                            'ORANGE' => 'bg-orange-50 text-orange-700 ring-1 ring-orange-600/20 dark:bg-orange-950/60 dark:text-orange-300',
                                            default => 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/60 dark:text-rose-300',
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
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $badgeStyle }}">
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
                        <div class="p-4 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-200 text-sm flex items-center gap-2">
                            <x-heroicon-m-check-circle class="w-5 h-5 text-emerald-600 flex-shrink-0" />
                            <span>No recurring priority areas flagged in this timeframe (all dimensions &ge; 85%).</span>
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
