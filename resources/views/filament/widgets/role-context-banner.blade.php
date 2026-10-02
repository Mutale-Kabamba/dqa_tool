<x-filament-widgets::widget>
    <div class="mb-2">
        @if($isMeal)
            {{-- MEAL OFFICER BANNER --}}
            <div class="relative overflow-hidden rounded-xl border border-amber-300/80 bg-gradient-to-r from-amber-500/10 via-amber-400/5 to-transparent p-4 shadow-xs dark:border-amber-700/60 dark:from-amber-950/40">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-500 text-white shadow-sm shadow-amber-500/20">
                            <x-heroicon-o-shield-check class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-gray-900 dark:text-white">MEAL Global Quality Oversight Cockpit</h2>
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800 dark:bg-amber-900/60 dark:text-amber-200">
                                    Global Scope
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                                Central governance: RAG threshold administration, central auditor dispatch engine, user management, and final verification sign-off.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('filament.admin.resources.audit-assignments.index') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-amber-700 transition">
                            <x-heroicon-m-paper-airplane class="h-3.5 w-3.5" />
                            Dispatch Queue
                        </a>
                        <a href="{{ route('filament.admin.resources.settings.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition">
                            <x-heroicon-m-cog-6-tooth class="h-3.5 w-3.5 text-gray-500" />
                            RAG Thresholds
                        </a>
                        <a href="{{ route('filament.admin.resources.users.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition">
                            <x-heroicon-m-users class="h-3.5 w-3.5 text-gray-500" />
                            Users
                        </a>
                    </div>
                </div>
            </div>
        @elseif($isDualRole)
            {{-- DUAL ROLE: PROJECT OFFICER & PEER AUDITOR BANNER --}}
            <div class="relative overflow-hidden rounded-xl border border-indigo-300/80 bg-gradient-to-r from-indigo-500/10 via-purple-400/5 to-transparent p-4 shadow-xs dark:border-indigo-700/60 dark:from-indigo-950/40">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white shadow-sm shadow-indigo-600/20">
                            <x-heroicon-o-arrows-right-left class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-gray-900 dark:text-white">Project Quality and Peer Reviewer Cockpit</h2>
                                <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-semibold text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-200">
                                    Dual-Role Active
                                </span>
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200">
                                    Anti-Self-Audit Enforced
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                                Overseeing <strong class="text-indigo-700 dark:text-indigo-300 font-semibold">{{ $projectsLabel }}</strong> performance and CAPAs, with peer review audit clearance across other project sites.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('filament.admin.resources.audit-submissions.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 transition">
                            <x-heroicon-m-arrow-up-tray class="h-3.5 w-3.5" />
                            Submit Data File
                        </a>
                        <a href="{{ route('filament.admin.pages.consolidated-project-view') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition">
                            <x-heroicon-m-presentation-chart-line class="h-3.5 w-3.5 text-indigo-500" />
                            Consolidated Review
                        </a>
                    </div>
                </div>
            </div>
        @elseif($isPO)
            {{-- PROJECT OFFICER BANNER --}}
            <div class="relative overflow-hidden rounded-xl border border-blue-300/80 bg-gradient-to-r from-blue-500/10 via-sky-400/5 to-transparent p-4 shadow-xs dark:border-blue-700/60 dark:from-blue-950/40">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white shadow-sm shadow-blue-600/20">
                            <x-heroicon-o-folder class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-gray-900 dark:text-white">Project Officer Quality Cockpit</h2>
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-[11px] font-semibold text-blue-800 dark:bg-blue-900/60 dark:text-blue-200">
                                    {{ $projectsLabel }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                                Monitors facility data reports, audits completed across project sites, and drives resolution of assigned Corrective and Preventive Action (CAPA) items.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('filament.admin.resources.audit-submissions.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-blue-700 transition">
                            <x-heroicon-m-arrow-up-tray class="h-3.5 w-3.5" />
                            Submit Data File
                        </a>
                        <a href="{{ route('filament.admin.pages.consolidated-project-view') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition">
                            <x-heroicon-m-presentation-chart-line class="h-3.5 w-3.5 text-blue-500" />
                            Consolidated Review
                        </a>
                    </div>
                </div>
            </div>
        @elseif($isAuditor)
            {{-- AUDITOR BANNER --}}
            <div class="relative overflow-hidden rounded-xl border border-emerald-300/80 bg-gradient-to-r from-emerald-500/10 via-teal-400/5 to-transparent p-4 shadow-xs dark:border-emerald-700/60 dark:from-emerald-950/40">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-sm shadow-emerald-600/20">
                            <x-heroicon-o-clipboard-document-check class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-gray-900 dark:text-white">Field and Peer Auditor Workspace</h2>
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200">
                                    Assigned Audit Queue
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                                Conducts dimensional checks on assigned site records, inputs compliant tallies, logs qualitative observations, and submits audit dossiers.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('filament.admin.resources.assigned-audits.index') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-emerald-700 transition">
                            <x-heroicon-m-queue-list class="h-3.5 w-3.5" />
                            Assigned Tasks Queue
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
