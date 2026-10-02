<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
    <table class="w-full text-left text-sm divide-y divide-gray-200 dark:divide-gray-800">
        <thead class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
            <tr>
                <th class="px-4 py-2.5">Dimension</th>
                <th class="px-4 py-2.5">Root Cause</th>
                <th class="px-4 py-2.5">Identified Gap & Action Plan</th>
                <th class="px-4 py-2.5">Responsible</th>
                <th class="px-4 py-2.5">Due Date</th>
                <th class="px-4 py-2.5 text-center">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800/60 dark:bg-gray-900">
            @php
                $actionItems = $getRecord()?->actionItems ?? collect();
            @endphp
            @forelse ($actionItems as $item)
                @php
                    $status = strtoupper($item->status ?? 'OPEN');
                    $badgeStyle = match($status) {
                        'RESOLVED' => 'background-color: #dcfce7; color: #15803d; border: 1px solid #86efac;',
                        'IN_PROGRESS' => 'background-color: #e0f2fe; color: #0369a1; border: 1px solid #7dd3fc;',
                        'OVERDUE' => 'background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;',
                        default => 'background-color: #fef3c7; color: #b45309; border: 1px solid #fcd34d;',
                    };
                @endphp
                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                    <td class="px-4 py-2.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                            {{ $item->dimension_name }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 font-medium text-gray-800 dark:text-gray-200 text-xs">
                        {{ $item->root_cause_category }}
                    </td>
                    <td class="px-4 py-2.5">
                        <div class="font-medium text-gray-900 dark:text-gray-100 text-xs">{{ $item->issue_description }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"><span class="font-semibold text-gray-600 dark:text-gray-300">Action:</span> {{ $item->action_plan }}</div>
                        @if ($item->resolution_notes)
                            <div class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5"><span class="font-semibold">Notes:</span> {{ $item->resolution_notes }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-xs text-gray-700 dark:text-gray-300 font-medium">
                        {{ $item->responsible_person }}
                    </td>
                    <td class="px-4 py-2.5 text-xs font-mono text-gray-600 dark:text-gray-400 whitespace-nowrap">
                        {{ $item->due_date?->format('M j, Y') ?? 'N/A' }}
                    </td>
                    <td class="px-4 py-2.5 text-center whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold tracking-wide shadow-xs" style="{{ $badgeStyle }}">
                            {{ $status }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        No CAPA remediation action items registered for this audit.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
