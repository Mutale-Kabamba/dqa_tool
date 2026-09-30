<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-heroicon-m-table-cells class="w-5 h-5 text-primary-500" />
                    <span>5-Dimension Quality Scorecard</span>
                </div>
                <div class="flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background-color: #10b981;"></span> Green &ge; {{ number_format($green_threshold * 100, 0) }}%</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background-color: #f59e0b;"></span> Yellow &ge; {{ number_format($yellow_threshold * 100, 0) }}%</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background-color: #f97316;"></span> Orange &ge; {{ number_format($orange_threshold * 100, 0) }}%</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background-color: #ef4444;"></span> Red &lt; {{ number_format($orange_threshold * 100, 0) }}%</span>
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
                            $ragConfig = match($dim['status']) {
                                'GREEN' => [
                                    'badgeStyle' => 'background-color: #dcfce7; color: #166534; border: 1px solid #86efac;',
                                    'barColor' => '#10b981',
                                    'scoreColor' => '#15803d',
                                ],
                                'YELLOW' => [
                                    'badgeStyle' => 'background-color: #fef3c7; color: #92400e; border: 1px solid #fcd34d;',
                                    'barColor' => '#f59e0b',
                                    'scoreColor' => '#b45309',
                                ],
                                'ORANGE' => [
                                    'badgeStyle' => 'background-color: #ffedd5; color: #9a3412; border: 1px solid #fdba74;',
                                    'barColor' => '#f97316',
                                    'scoreColor' => '#c2410c',
                                ],
                                default => [
                                    'badgeStyle' => 'background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;',
                                    'barColor' => '#ef4444',
                                    'scoreColor' => '#dc2626',
                                ],
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
                                <div class="relative w-full rounded-full h-2.5 overflow-hidden" style="background-color: #e5e7eb;">
                                    <div class="h-2.5 rounded-full transition-all duration-500" style="width: {{ $widthPct }}%; background-color: {{ $ragConfig['barColor'] }};"></div>
                                </div>
                                <div class="flex justify-between text-[10px] text-gray-400 mt-1 font-medium">
                                    <span>0%</span>
                                    <span style="color: #059669; font-weight: 700;">&bull; 85% Target</span>
                                    <span>100%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-center font-mono font-bold text-sm text-gray-900 dark:text-gray-100">
                                {{ $dim['score_pct'] }}%
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold tracking-wider shadow-sm" style="{{ $ragConfig['badgeStyle'] }}">
                                    {{ $dim['status'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-900/80 font-bold border-t-2 border-gray-200 dark:border-gray-700">
                    @php
                        $overallSolidStyle = match($overall_status) {
                            'GREEN' => 'background-color: #10b981; color: #ffffff; border: 1px solid #059669; box-shadow: 0 1px 3px rgba(16, 185, 129, 0.4);',
                            'YELLOW' => 'background-color: #f59e0b; color: #ffffff; border: 1px solid #d97706; box-shadow: 0 1px 3px rgba(245, 158, 11, 0.4);',
                            'ORANGE' => 'background-color: #f97316; color: #ffffff; border: 1px solid #ea580c; box-shadow: 0 1px 3px rgba(249, 115, 22, 0.4);',
                            default => 'background-color: #ef4444; color: #ffffff; border: 1px solid #dc2626; box-shadow: 0 1px 3px rgba(239, 68, 68, 0.4);',
                        };
                    @endphp
                    <tr>
                        <td class="px-4 py-3.5 text-gray-900 dark:text-gray-100 uppercase tracking-wider text-xs font-extrabold">
                            Overall Portfolio Total (Pooled Micro-Average)
                        </td>
                        <td class="px-4 py-3.5 text-right font-mono text-base text-gray-900 dark:text-gray-100">
                            {{ number_format($grand_checked) }}
                        </td>
                        <td class="px-4 py-3.5 text-right font-mono text-base font-bold" style="color: #059669;">
                            {{ number_format($grand_compliant) }}
                        </td>
                        <td class="px-4 py-3.5 text-xs text-gray-500">
                            &sum; Compliant / &sum; Checked
                        </td>
                        <td class="px-4 py-3.5 text-center font-mono font-extrabold text-base text-gray-900 dark:text-gray-100">
                            {{ $overall_score_pct }}%
                        </td>
                        <td class="px-4 py-3.5 text-center">
                            <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-black tracking-widest uppercase" style="{{ $overallSolidStyle }}">
                                {{ $overall_status }}
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
