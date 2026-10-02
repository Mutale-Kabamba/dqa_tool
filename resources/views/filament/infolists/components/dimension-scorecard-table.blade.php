<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
    <table class="w-full text-left text-sm divide-y divide-gray-200 dark:divide-gray-800">
        <thead class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
            <tr>
                <th class="px-4 py-2.5">Dimension</th>
                <th class="px-4 py-2.5 text-right">Checked</th>
                <th class="px-4 py-2.5 text-right">Compliant</th>
                <th class="px-4 py-2.5 w-40">Progress</th>
                <th class="px-4 py-2.5 text-center">Compliance %</th>
                <th class="px-4 py-2.5 text-center">RAG Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800/60 dark:bg-gray-900">
            @php
                $dimensions = $getRecord()?->dimensions ?? collect();
            @endphp
            @forelse ($dimensions as $dim)
                @php
                    $scorePct = (float) $dim->score_percentage * 100;
                    $status = strtoupper($dim->status ?? 'RED');
                    
                    $badgeStyle = match($status) {
                        'GREEN' => 'background-color: #dcfce7; color: #15803d; border: 1px solid #86efac;',
                        'YELLOW' => 'background-color: #fef3c7; color: #b45309; border: 1px solid #fcd34d;',
                        'ORANGE' => 'background-color: #ffedd5; color: #c2410c; border: 1px solid #fdba74;',
                        default => 'background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;',
                    };

                    $barColor = match($status) {
                        'GREEN' => '#10b981',
                        'YELLOW' => '#f59e0b',
                        'ORANGE' => '#f97316',
                        default => '#ef4444',
                    };
                    $widthPct = min(100, max(0, $scorePct));
                @endphp
                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                    <td class="px-4 py-2.5 font-semibold text-gray-900 dark:text-gray-100">
                        {{ $dim->dimension_name }}
                    </td>
                    <td class="px-4 py-2.5 text-right font-mono text-gray-700 dark:text-gray-300">
                        {{ number_format($dim->checked_count) }}
                    </td>
                    <td class="px-4 py-2.5 text-right font-mono font-medium text-gray-900 dark:text-gray-100">
                        {{ number_format($dim->compliant_count) }}
                    </td>
                    <td class="px-4 py-2.5">
                        <div class="relative w-full rounded-full h-2 overflow-hidden bg-gray-200 dark:bg-gray-700">
                            <div class="h-2 rounded-full transition-all duration-300" style="width: {{ $widthPct }}%; background-color: {{ $barColor }};"></div>
                        </div>
                    </td>
                    <td class="px-4 py-2.5 text-center font-mono font-bold text-gray-900 dark:text-gray-100">
                        {{ number_format($scorePct, 1) }}%
                    </td>
                    <td class="px-4 py-2.5 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold tracking-wide shadow-xs" style="{{ $badgeStyle }}">
                            {{ $status }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        No dimensional tally records entered yet.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($getRecord() && $dimensions->isNotEmpty())
            @php
                $overallChecked = $getRecord()->overall_checked ?? $dimensions->sum('checked_count');
                $overallCompliant = $getRecord()->overall_compliant ?? $dimensions->sum('compliant_count');
                $overallScorePct = (float) $getRecord()->overall_score * 100;
                $overallStatus = strtoupper($getRecord()->overall_status ?? 'RED');
                
                $overallSolidStyle = match($overallStatus) {
                    'GREEN' => 'background-color: #10b981; color: #ffffff; border: 1px solid #059669;',
                    'YELLOW' => 'background-color: #f59e0b; color: #ffffff; border: 1px solid #d97706;',
                    'ORANGE' => 'background-color: #f97316; color: #ffffff; border: 1px solid #ea580c;',
                    default => 'background-color: #ef4444; color: #ffffff; border: 1px solid #dc2626;',
                };
            @endphp
            <tfoot class="bg-gray-50/90 font-bold border-t border-gray-200 dark:border-gray-700 dark:bg-gray-800/80">
                <tr>
                    <td class="px-4 py-2.5 text-xs uppercase tracking-wider text-gray-700 dark:text-gray-300">
                        Overall Audit Composite Score
                    </td>
                    <td class="px-4 py-2.5 text-right font-mono text-gray-900 dark:text-gray-100">
                        {{ number_format($overallChecked) }}
                    </td>
                    <td class="px-4 py-2.5 text-right font-mono text-emerald-600 dark:text-emerald-400">
                        {{ number_format($overallCompliant) }}
                    </td>
                    <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400 text-center font-normal">
                        Pooled Audit Quality
                    </td>
                    <td class="px-4 py-2.5 text-center font-mono text-base font-extrabold text-gray-900 dark:text-gray-100">
                        {{ number_format($overallScorePct, 1) }}%
                    </td>
                    <td class="px-4 py-2.5 text-center">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black tracking-wider uppercase shadow-sm" style="{{ $overallSolidStyle }}">
                            {{ $overallStatus }}
                        </span>
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
