<div class="fi-wi-stats-overview grid gap-4 md:grid-cols-2">
    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-700 to-indigo-800 text-white rounded-xl shadow-md border border-blue-600">
        <div class="flex items-center space-x-3">
            <div class="p-3 bg-white/10 rounded-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg>
            </div>
            <div>
                <h4 class="text-base font-bold">Submit New Data File for Audit</h4>
                <p class="text-xs text-blue-100">Upload routine monthly/quarterly register summaries & datasets for review</p>
            </div>
        </div>
        <a href="{{ \App\Filament\Resources\AuditSubmissionResource::getUrl('create') }}" 
           class="inline-flex items-center px-4 py-2 bg-white text-blue-800 font-semibold text-xs uppercase tracking-wider rounded-lg shadow hover:bg-blue-50 transition">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Upload File
        </a>
    </div>

    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-emerald-700 to-teal-800 text-white rounded-xl shadow-md border border-emerald-600">
        <div class="flex items-center space-x-3">
            <div class="p-3 bg-white/10 rounded-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <div>
                <h4 class="text-base font-bold">Consolidated Project Performance Review</h4>
                <p class="text-xs text-emerald-100">Access full project rollups, comparative site audits, and data exports</p>
            </div>
        </div>
        <a href="{{ \App\Filament\Pages\ConsolidatedProjectView::getUrl() }}" 
           class="inline-flex items-center px-4 py-2 bg-white text-emerald-800 font-semibold text-xs uppercase tracking-wider rounded-lg shadow hover:bg-emerald-50 transition">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
            View Review
        </a>
    </div>
</div>
