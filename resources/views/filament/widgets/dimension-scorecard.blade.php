<x-filament-widgets::widget>
    <x-filament::section :collapsible="true" :compact="true">
        <x-slot name="heading">
            <div class="flex flex-wrap items-center justify-between gap-3 w-full pr-8">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg" style="background-color: rgba(2, 132, 199, 0.12); color: #0284c7;">
                        <x-heroicon-m-table-cells class="w-5 h-5" />
                    </div>
                    <div>
                        <span class="font-bold text-sm text-gray-900 dark:text-white">5-Dimension Quality Scorecard</span>
                        <span class="hidden sm:inline-block text-[11px] text-gray-500 dark:text-gray-400 ml-2 font-normal">&bull; Target Quality Benchmark: <strong>85.0%</strong></span>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 text-[11px] font-semibold">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md" style="background-color: #dcfce7; color: #166534; border: 1px solid #86efac;">
                        <span class="w-2 h-2 rounded-full" style="background-color: #10b981;"></span> &ge;{{ number_format($green_threshold * 100, 0) }}% Green
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md" style="background-color: #fef3c7; color: #92400e; border: 1px solid #fcd34d;">
                        <span class="w-2 h-2 rounded-full" style="background-color: #f59e0b;"></span> &ge;{{ number_format($yellow_threshold * 100, 0) }}% Yellow
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md" style="background-color: #ffedd5; color: #9a3412; border: 1px solid #fdba74;">
                        <span class="w-2 h-2 rounded-full" style="background-color: #f97316;"></span> &ge;{{ number_format($orange_threshold * 100, 0) }}% Orange
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md" style="background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;">
                        <span class="w-2 h-2 rounded-full" style="background-color: #ef4444;"></span> &lt;{{ number_format($orange_threshold * 100, 0) }}% Red
                    </span>
                </div>
            </div>
        </x-slot>

        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-xs">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-gray-50 dark:bg-gray-800/70 border-b border-gray-200 dark:border-gray-700 text-[11px] uppercase tracking-wider font-bold text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-2.5" style="width: 22%;">Dimension</th>
                        <th class="px-4 py-2.5 text-center" style="width: 18%;">Verified Records</th>
                        <th class="px-4 py-2.5" style="width: 38%;">Performance vs 85% Target</th>
                        <th class="px-4 py-2.5 text-center" style="width: 11%;">Score</th>
                        <th class="px-4 py-2.5 text-center" style="width: 11%;">RAG Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($dimensions as $dim)
                        @php
                            $status = $dim['status'];
                            $widthPct = min(100, max(0, $dim['score'] * 100));

                            $dimIcon = match($dim['name']) {
                                'Accuracy' => 'heroicon-m-check-badge',
                                'Completeness' => 'heroicon-m-document-check',
                                'Consistency' => 'heroicon-m-arrows-right-left',
                                'Timeliness' => 'heroicon-m-clock',
                                'Validity' => 'heroicon-m-shield-check',
                                default => 'heroicon-m-chart-pie',
                            };

                            $colorScheme = match($status) {
                                'GREEN' => [
                                    'borderLeft' => '#10b981',
                                    'iconBg' => '#dcfce7',
                                    'iconColor' => '#15803d',
                                    'barGradient' => 'linear-gradient(90deg, #34d399 0%, #059669 100%)',
                                    'scoreColor' => '#059669',
                                    'badgeBg' => '#dcfce7',
                                    'badgeColor' => '#15803d',
                                    'badgeBorder' => '#86efac',
                                    'dot' => '#10b981',
                                ],
                                'YELLOW' => [
                                    'borderLeft' => '#f59e0b',
                                    'iconBg' => '#fef3c7',
                                    'iconColor' => '#b45309',
                                    'barGradient' => 'linear-gradient(90deg, #fde047 0%, #d97706 100%)',
                                    'scoreColor' => '#b45309',
                                    'badgeBg' => '#fef3c7',
                                    'badgeColor' => '#92400e',
                                    'badgeBorder' => '#fcd34d',
                                    'dot' => '#f59e0b',
                                ],
                                'ORANGE' => [
                                    'borderLeft' => '#f97316',
                                    'iconBg' => '#ffedd5',
                                    'iconColor' => '#c2410c',
                                    'barGradient' => 'linear-gradient(90deg, #fdba74 0%, #ea580c 100%)',
                                    'scoreColor' => '#c2410c',
                                    'badgeBg' => '#ffedd5',
                                    'badgeColor' => '#9a3412',
                                    'badgeBorder' => '#fdba74',
                                    'dot' => '#f97316',
                                ],
                                default => [
                                    'borderLeft' => '#ef4444',
                                    'iconBg' => '#fee2e2',
                                    'iconColor' => '#b91c1c',
                                    'barGradient' => 'linear-gradient(90deg, #fca5a5 0%, #dc2626 100%)',
                                    'scoreColor' => '#dc2626',
                                    'badgeBg' => '#fee2e2',
                                    'badgeColor' => '#991b1b',
                                    'badgeBorder' => '#fca5a5',
                                    'dot' => '#ef4444',
                                ],
                            };
                        @endphp
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition-colors" style="border-left: 4px solid {{ $colorScheme['borderLeft'] }};">
                            {{-- Dimension Column --}}
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg shadow-2xs" 
                                         style="background-color: {{ $colorScheme['iconBg'] }}; color: {{ $colorScheme['iconColor'] }};">
                                        <x-filament::icon :icon="$dimIcon" class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-gray-100 text-xs flex items-center gap-1.5">
                                            {{ $dim['name'] }}
                                            <span title="{{ $dim['definition'] }}" class="cursor-help text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                                <x-heroicon-m-information-circle class="w-3.5 h-3.5 inline opacity-75" />
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Verified Records Column --}}
                            <td class="px-4 py-2.5 text-center font-mono text-xs">
                                <span class="font-bold text-gray-900 dark:text-gray-100" style="color: {{ $colorScheme['scoreColor'] }};">{{ number_format($dim['compliant']) }}</span>
                                <span class="text-gray-400 dark:text-gray-500 font-normal">/</span>
                                <span class="text-gray-600 dark:text-gray-400 font-medium">{{ number_format($dim['checked']) }}</span>
                                <span class="text-[10px] text-gray-400 font-sans ml-0.5">items</span>
                            </td>

                            {{-- Benchmark Progress Bar Column --}}
                            <td class="px-4 py-2.5">
                                <div class="relative w-full py-1">
                                    {{-- Bar Track --}}
                                    <div style="height: 10px; width: 100%; min-width: 140px; background-color: #e2e8f0; border-radius: 9999px; position: relative; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.06);">
                                        {{-- Colored Fill --}}
                                        <div style="height: 100%; width: {{ $widthPct }}%; background: {{ $colorScheme['barGradient'] }}; border-radius: 9999px; transition: width 0.5s ease-in-out;"></div>
                                    </div>
                                    {{-- 85% Target Pin Marker --}}
                                    <div style="position: absolute; top: 0; bottom: 0; left: 85%; width: 2px; background-color: #047857; z-index: 10;" 
                                         title="85% National Benchmark Target Line">
                                        <div style="position: absolute; top: -3px; left: -3px; width: 8px; height: 3px; background-color: #047857; border-radius: 2px;"></div>
                                        <div style="position: absolute; bottom: -3px; left: -3px; width: 8px; height: 3px; background-color: #047857; border-radius: 2px;"></div>
                                    </div>
                                </div>
                            </td>

                            {{-- Score % Column --}}
                            <td class="px-4 py-2.5 text-center font-mono font-bold text-xs" style="color: {{ $colorScheme['scoreColor'] }};">
                                {{ $dim['score_pct'] }}%
                            </td>

                            {{-- RAG Status Badge Column --}}
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-extrabold tracking-wide shadow-2xs" 
                                      style="background-color: {{ $colorScheme['badgeBg'] }}; color: {{ $colorScheme['badgeColor'] }}; border: 1px solid {{ $colorScheme['badgeBorder'] }};">
                                    <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $colorScheme['dot'] }};"></span>
                                    {{ $status }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                {{-- Overall Portfolio Total Footer --}}
                <tfoot class="bg-gray-50/95 dark:bg-gray-800/90 border-t-2 border-gray-300 dark:border-gray-600 font-bold" style="border-left: 4px solid #059669;">
                    @php
                        $overallGradient = match($overall_status) {
                            'GREEN' => 'linear-gradient(90deg, #10b981 0%, #047857 100%)',
                            'YELLOW' => 'linear-gradient(90deg, #f59e0b 0%, #d97706 100%)',
                            'ORANGE' => 'linear-gradient(90deg, #f97316 0%, #ea580c 100%)',
                            default => 'linear-gradient(90deg, #ef4444 0%, #dc2626 100%)',
                        };

                        $overallSolidStyle = match($overall_status) {
                            'GREEN' => 'background-color: #059669; color: #ffffff; border: 1px solid #047857;',
                            'YELLOW' => 'background-color: #d97706; color: #ffffff; border: 1px solid #b45309;',
                            'ORANGE' => 'background-color: #ea580c; color: #ffffff; border: 1px solid #c2410c;',
                            default => 'background-color: #dc2626; color: #ffffff; border: 1px solid #b91c1c;',
                        };
                        $overallWidthPct = min(100, max(0, $overall_score * 100));
                    @endphp
                    <tr>
                        <td class="px-4 py-3 font-extrabold text-gray-900 dark:text-white text-xs">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full" style="background-color: #059669;"></span>
                                <span class="uppercase tracking-wider">Overall Portfolio Total</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center font-mono font-bold text-xs">
                            <span style="color: #059669;">{{ number_format($grand_compliant) }}</span>
                            <span class="text-gray-400">/</span>
                            <span class="text-gray-900 dark:text-white">{{ number_format($grand_checked) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="relative w-full py-1">
                                <div style="height: 10px; width: 100%; min-width: 140px; background-color: #cbd5e1; border-radius: 9999px; position: relative; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);">
                                    <div style="height: 100%; width: {{ $overallWidthPct }}%; background: {{ $overallGradient }}; border-radius: 9999px; transition: width 0.5s ease;"></div>
                                </div>
                                <div style="position: absolute; top: 0; bottom: 0; left: 85%; width: 2px; background-color: #047857; z-index: 10;">
                                    <div style="position: absolute; top: -3px; left: -3px; width: 8px; height: 3px; background-color: #047857; border-radius: 2px;"></div>
                                    <div style="position: absolute; bottom: -3px; left: -3px; width: 8px; height: 3px; background-color: #047857; border-radius: 2px;"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center font-mono font-black text-sm" style="color: #059669;">
                            {{ $overall_score_pct }}%
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-black tracking-wider uppercase shadow-sm" style="{{ $overallSolidStyle }}">
                                {{ $overall_status }}
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
