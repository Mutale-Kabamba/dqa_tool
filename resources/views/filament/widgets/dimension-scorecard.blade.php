<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-heroicon-m-table-cells class="w-5 h-5 text-primary-500" />
                    <span>5-Dimension Quality Scorecard</span>
                </div>
                <div class="flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Green &ge; {{ number_format($green_threshold * 100, 0) }}%</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Yellow &ge; {{ number_format($yellow_threshold * 100, 0) }}%</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span> Orange &ge; {{ number_format($orange_threshold * 100, 0) }}%</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Red &lt; {{ number_format($orange_threshold * 100, 0) }}%</span>
                </div>
            </div>
        </x-slot>

        <x-slot name="description">
            Consolidated micro-average performance across all audited field records in the active filter selection
        </x-slot>

        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
            <table class="w-full text-left text-sm divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900/60 text-xs uppercase font-semibold text-gray-600 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Dimension</th>
                        <th class="px-4 py-3 text-right">Checked</th>
                        <th class="px-4 py-3 text-right">Compliant</th>
                        <th class="px-4 py-3 w-48">Compliance Benchmark</th>
                        <th class="px-4 py-3 text-center">Score %</th>
                        <th class="px-4 py-3 text-center">RAG Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 bg-white dark:bg-gray-900">
                    @foreach ($dimensions as $dim)
                        @php
                            $badgeClasses = match($dim['status']) {
                                'GREEN' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300',
                                'YELLOW' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/60 dark:text-amber-300',
                                'ORANGE' => 'bg-orange-50 text-orange-700 ring-1 ring-orange-600/20 dark:bg-orange-950/60 dark:text-orange-300',
                                default => 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/60 dark:text-rose-300',
                            };
                            $barBg = match($dim['status']) {
                                'GREEN' => 'bg-emerald-500',
                                'YELLOW' => 'bg-amber-500',
                                'ORANGE' => 'bg-orange-500',
                                default => 'bg-rose-500',
                            };
                            $widthPct = min(100, max(0, $dim['score'] * 100));
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition">
                            <td class="px-4 py-3.5">
                                <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $dim['name'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 max-w-sm">{{ $dim['definition'] }}</div>
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-gray-700 dark:text-gray-300">
                                {{ number_format($dim['checked']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono font-medium text-gray-900 dark:text-gray-100">
                                {{ number_format($dim['compliant']) }}
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="relative w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2.5 overflow-hidden">
                                    <div class="h-2.5 rounded-full {{ $barBg }} transition-all duration-500" style="width: {{ $widthPct }}%"></div>
                                </div>
                                <div class="flex justify-between text-[10px] text-gray-400 mt-1">
                                    <span>0%</span>
                                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">&bull; 85% Target</span>
                                    <span>100%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-center font-mono font-bold text-sm text-gray-900 dark:text-gray-100">
                                {{ $dim['score_pct'] }}%
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold tracking-wider {{ $badgeClasses }}">
                                    {{ $dim['status'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-900/80 font-bold border-t-2 border-gray-200 dark:border-gray-700">
                    @php
                        $overallBadge = match($overall_status) {
                            'GREEN' => 'bg-emerald-600 text-white',
                            'YELLOW' => 'bg-amber-500 text-white',
                            'ORANGE' => 'bg-orange-600 text-white',
                            default => 'bg-rose-600 text-white',
                        };
                    @endphp
                    <tr>
                        <td class="px-4 py-3.5 text-gray-900 dark:text-gray-100 uppercase tracking-wider text-xs font-extrabold">
                            Overall Portfolio Total (Pooled Micro-Average)
                        </td>
                        <td class="px-4 py-3.5 text-right font-mono text-base text-gray-900 dark:text-gray-100">
                            {{ number_format($grand_checked) }}
                        </td>
                        <td class="px-4 py-3.5 text-right font-mono text-base text-emerald-600 dark:text-emerald-400">
                            {{ number_format($grand_compliant) }}
                        </td>
                        <td class="px-4 py-3.5 text-xs text-gray-500">
                            &sum; Compliant / &sum; Checked
                        </td>
                        <td class="px-4 py-3.5 text-center font-mono font-extrabold text-base text-gray-900 dark:text-gray-100">
                            {{ $overall_score_pct }}%
                        </td>
                        <td class="px-4 py-3.5 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black tracking-widest {{ $overallBadge }} shadow-sm">
                                {{ $overall_status }}
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
