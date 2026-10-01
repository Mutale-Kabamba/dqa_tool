<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DQA Audit Dossier - {{ $audit->audit_code }}</title>
    <style>
        @page {
            margin: 28px 32px 35px 32px;
            size: A4 portrait;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }

        .header-table {
            width: 100%;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .brand-title {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
            margin: 0;
            text-transform: uppercase;
        }

        .brand-subtitle {
            font-size: 10px;
            font-weight: 600;
            color: #0284c7;
            margin: 2px 0 0 0;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .audit-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #0f172a;
            padding: 4px 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
            font-weight: bold;
            border: 1px solid #cbd5e1;
        }

        .meta-grid {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: collapse;
        }

        .meta-grid td {
            padding: 4px 6px;
            vertical-align: top;
            font-size: 10.5px;
        }

        .meta-label {
            font-size: 9px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            display: block;
            margin-bottom: 1px;
        }

        .meta-value {
            font-weight: 600;
            color: #0f172a;
        }

        /* Status Banners */
        .status-banner {
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 14px;
        }

        .status-banner.GREEN {
            background-color: #ecfdf5;
            border: 1.5px solid #10b981;
            color: #065f46;
        }

        .status-banner.YELLOW {
            background-color: #fffbeb;
            border: 1.5px solid #f59e0b;
            color: #92400e;
        }

        .status-banner.ORANGE {
            background-color: #fff7ed;
            border: 1.5px solid #f97316;
            color: #9a3412;
        }

        .status-banner.RED {
            background-color: #fef2f2;
            border: 1.5px solid #ef4444;
            color: #991b1b;
        }

        .status-pill {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #ffffff;
        }

        .status-pill.GREEN { background-color: #059669; }
        .status-pill.YELLOW { background-color: #d97706; }
        .status-pill.ORANGE { background-color: #ea580c; }
        .status-pill.RED { background-color: #dc2626; }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .data-table th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 6px 8px;
            border-bottom: 1.5px solid #cbd5e1;
            border-top: 1px solid #e2e8f0;
        }

        .data-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 10.5px;
        }

        .data-table tr.total-row td {
            background-color: #f8fafc;
            font-weight: bold;
            border-top: 1.5px solid #94a3b8;
            border-bottom: 2px solid #64748b;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: monospace; }
        .font-bold { font-weight: bold; }

        /* Alert Box */
        .alert-box {
            padding: 9px 12px;
            border-radius: 5px;
            margin-bottom: 14px;
            font-size: 10px;
        }

        .alert-warning {
            background-color: #fff1f2;
            border: 1px solid #fecdd3;
            color: #9f1239;
        }

        /* Section Blocks */
        .section-title {
            font-size: 11px;
            font-weight: 800;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 14px;
            margin-bottom: 8px;
        }

        .notes-card {
            background: #fafafa;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            padding: 8px 10px;
            margin-bottom: 10px;
            font-size: 10px;
            min-height: 48px;
        }

        .page-break {
            page-break-before: always;
        }

        /* Signatures */
        .signature-table {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
        }

        .signature-table td {
            width: 50%;
            padding: 10px 16px;
            vertical-align: top;
        }

        .signature-box {
            border: 1px dashed #94a3b8;
            background: #fafafa;
            border-radius: 6px;
            padding: 12px 14px;
        }

        .signature-line {
            margin-top: 35px;
            border-top: 1px solid #475569;
            padding-top: 4px;
        }

        .footer {
            position: fixed;
            bottom: 0px;
            left: 0;
            right: 0;
            font-size: 8.5px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <div class="footer">
        Data Quality Audit (DQA) System &bull; Confidential Verification Dossier &bull; Generated: {{ now()->format('Y-m-d H:i') }} &bull; Page 1 of 2
    </div>

    <!-- PAGE 1: HEADER & 5-DIMENSION SCORECARD -->
    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <div class="brand-title">Light Data Quality Audit (DQA)</div>
                <div class="brand-subtitle">Standardized Field Verification Dossier</div>
            </td>
            <td style="width: 30%; text-align: right;">
                <div class="audit-badge">{{ $audit->audit_code }}</div>
            </td>
        </tr>
    </table>

    <!-- Metadata Section -->
    <table class="meta-grid">
        <tr>
            <td style="width: 33%;">
                <span class="meta-label">Project Name</span>
                <span class="meta-value">{{ $audit->project?->name ?? 'N/A' }} ({{ $audit->project?->code }})</span>
            </td>
            <td style="width: 34%;">
                <span class="meta-label">Facility / Site Audited</span>
                <span class="meta-value">{{ $audit->site_name }}</span>
            </td>
            <td style="width: 33%;">
                <span class="meta-label">Audit Date</span>
                <span class="meta-value">{{ $audit->audit_date?->format('F d, Y') }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="meta-label">Lead Field Auditor</span>
                <span class="meta-value">{{ $audit->auditor_name }}</span>
            </td>
            <td>
                <span class="meta-label">Project Officer</span>
                <span class="meta-value">{{ $audit->facility_in_charge ?? 'Not Recorded' }}</span>
            </td>
            <td>
                <span class="meta-label">Reporting Period</span>
                <span class="meta-value">{{ $audit->period_label }} (Q{{ $audit->period_quarter }} {{ $audit->period_year }})</span>
            </td>
        </tr>
    </table>

    <!-- Overall Performance Banner -->
    <div class="status-banner {{ $audit->overall_status }}">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 70%;">
                    <div style="font-size: 14px; font-weight: bold;">
                        Overall Health Classification:
                        <span class="status-pill {{ $audit->overall_status }}">{{ $audit->overall_status }}</span>
                    </div>
                    <div style="font-size: 10px; margin-top: 3px; opacity: 0.9;">
                        Pooled Micro-Average: {{ number_format($audit->overall_compliant) }} of {{ number_format($audit->overall_checked) }} total sampled records compliant.
                    </div>
                </td>
                <td style="width: 30%; text-align: right;">
                    <div style="font-size: 26px; font-weight: 800; font-family: monospace;">
                        {{ number_format(((float) $audit->overall_score) * 100, 1) }}%
                    </div>
                    <div style="font-size: 8.5px; text-transform: uppercase; font-weight: bold; opacity: 0.8;">Composite Audit Score</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Five Dimension Scorecard Table -->
    <div class="section-title">Five-Dimension Performance Assessment</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Dimension</th>
                <th style="width: 35%;">Standard Definition</th>
                <th style="width: 12%;" class="text-right">Checked</th>
                <th style="width: 12%;" class="text-right">Compliant</th>
                <th style="width: 16%;" class="text-center">Score %</th>
                <th style="width: 14%;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @php
                $definitions = [
                    'Accuracy' => 'Data correctly reflects real-world persons, events or objects.',
                    'Completeness' => 'All mandatory fields/registers present without omissions.',
                    'Consistency' => 'Data agrees across registers, tallies and reports.',
                    'Timeliness' => 'Data captured, updated and reported within deadline.',
                    'Validity' => 'Data conforms to required types, formats and codes.',
                ];
            @endphp
            @foreach ($audit->dimensions as $dim)
                <tr>
                    <td class="font-bold">{{ $dim->dimension_name }}</td>
                    <td style="color: #64748b; font-size: 9px;">{{ $definitions[$dim->dimension_name] ?? '' }}</td>
                    <td class="text-right font-mono">{{ number_format($dim->checked_count) }}</td>
                    <td class="text-right font-mono font-bold">{{ number_format($dim->compliant_count) }}</td>
                    <td class="text-center font-mono font-bold">{{ number_format(((float) $dim->score_percentage) * 100, 1) }}%</td>
                    <td class="text-center">
                        <span class="status-pill {{ $dim->status }}" style="font-size: 9px; padding: 2px 7px;">
                            {{ $dim->status }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" style="text-transform: uppercase;">Overall Audit Total (Pooled Average)</td>
                <td class="text-right font-mono">{{ number_format($audit->overall_checked) }}</td>
                <td class="text-right font-mono">{{ number_format($audit->overall_compliant) }}</td>
                <td class="text-center font-mono font-bold" style="font-size: 12px;">{{ number_format(((float) $audit->overall_score) * 100, 1) }}%</td>
                <td class="text-center">
                    <span class="status-pill {{ $audit->overall_status }}" style="font-size: 9px; padding: 2px 7px;">
                        {{ $audit->overall_status }}
                    </span>
                </td>
            </tr>
        </tfoot>
    </table>

    @php
        $criticalDims = $audit->dimensions->filter(fn ($d) => ((float) $d->score_percentage) < 0.70);
    @endphp

    @if ($criticalDims->isNotEmpty())
        <div class="alert-box alert-warning">
            <strong>Escalation Warning:</strong> Significant programmatic intervention required on:
            <strong>{{ $criticalDims->pluck('dimension_name')->implode(', ') }}</strong> (&lt; 70% threshold).
        </div>
    @endif

    <div style="margin-top: 15px; font-size: 9.5px; color: #64748b;">
        <strong>Priority Attention Focus:</strong>
        {{ $audit->priority_areas ? $audit->priority_areas : 'None (All dimensions meet or exceed 85% Green benchmark).' }}
    </div>

    <!-- PAGE 2: QUALITATIVE FIELD NOTES & SIGN-OFF -->
    <div class="page-break"></div>

    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <div class="brand-title">Light Data Quality Audit (DQA)</div>
                <div class="brand-subtitle">Section 2: Qualitative Findings & Sign-Off Block</div>
            </td>
            <td style="width: 30%; text-align: right;">
                <div class="audit-badge">{{ $audit->audit_code }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">A. Operational Context & Root Cause Analysis</div>
    <div class="notes-card">
        @if ($audit->root_cause_notes)
            {{ $audit->root_cause_notes }}
        @else
            <em style="color: #94a3b8;">No specific operational root causes or negative environmental factors were recorded for this audit visit.</em>
        @endif
    </div>

    <div class="section-title">B. Actionable Recommendations & Agreed Next Steps</div>
    <div class="notes-card">
        @if ($audit->recommendations)
            {{ $audit->recommendations }}
        @else
            <em style="color: #94a3b8;">No general facility-level corrective action plan recorded. Routine monitoring continues.</em>
        @endif
    </div>

    @if ($audit->actionItems && $audit->actionItems->isNotEmpty())
        <div class="section-title">C. Corrective & Preventive Action (CAPA) Plan</div>
        <table class="data-table" style="font-size: 9.5px; margin-bottom: 12px;">
            <thead>
                <tr>
                    <th style="width: 14%;">Area</th>
                    <th style="width: 16%;">Root Cause</th>
                    <th style="width: 25%;">Identified Problem</th>
                    <th style="width: 25%;">Action Plan</th>
                    <th style="width: 10%;">Assigned</th>
                    <th style="width: 10%;">Due Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($audit->actionItems as $item)
                    <tr>
                        <td><strong>{{ $item->dimension_name ?? 'General' }}</strong></td>
                        <td>{{ $item->root_cause_category }}</td>
                        <td>{{ $item->issue_description }}</td>
                        <td>{{ $item->action_plan }}</td>
                        <td>{{ $item->responsible_person }}</td>
                        <td class="text-center font-mono">{{ $item->due_date ? $item->due_date->format('M j, Y') : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="section-title">{{ ($audit->actionItems && $audit->actionItems->isNotEmpty()) ? 'D' : 'C' }}. Benchmark Legend & Grading Scale Reference</div>
    <table style="width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 20px;">
        <tr>
            <td style="padding: 4px; width: 25%;">
                <span class="status-pill GREEN" style="font-size: 8px;">GREEN</span> <strong>&ge; 85%</strong>: Good Quality
            </td>
            <td style="padding: 4px; width: 25%;">
                <span class="status-pill YELLOW" style="font-size: 8px;">YELLOW</span> <strong>70% - 84%</strong>: Needs Improvement
            </td>
            <td style="padding: 4px; width: 25%;">
                <span class="status-pill ORANGE" style="font-size: 8px;">ORANGE</span> <strong>55% - 69%</strong>: Significant Action Needed
            </td>
            <td style="padding: 4px; width: 25%;">
                <span class="status-pill RED" style="font-size: 8px;">RED</span> <strong>&lt; 55%</strong>: Critical Risk
            </td>
        </tr>
    </table>

    <div class="section-title">D. Formal Verification & Sign-Off Block</div>
    <p style="font-size: 9.5px; color: #64748b; margin-top: 2px; margin-bottom: 12px;">
        By signing below, the lead auditor and facility representative certify that the sampled record counts, observations, and agreed next steps accurately reflect the on-site verification conducted.
    </p>

    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-box">
                    <div style="font-size: 10px; font-weight: bold; text-transform: uppercase; color: #1e293b;">
                        Field Auditor Sign-Off
                    </div>
                    <div style="font-size: 9px; color: #64748b; margin-top: 3px;">
                        Name: <strong>{{ $audit->auditor_name }}</strong>
                    </div>
                    <div class="signature-line">
                        <table style="width: 100%; font-size: 9px;">
                            <tr>
                                <td>Signature: ______________________</td>
                                <td style="text-align: right;">Date: ______________</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
            <td>
                <div class="signature-box">
                    <div style="font-size: 10px; font-weight: bold; text-transform: uppercase; color: #1e293b;">
                        Project Officer Sign-Off
                    </div>
                    <div style="font-size: 9px; color: #64748b; margin-top: 3px;">
                        Name: <strong>{{ $audit->facility_in_charge ?? 'Project Officer / Designee' }}</strong>
                    </div>
                    <div class="signature-line">
                        <table style="width: 100%; font-size: 9px;">
                            <tr>
                                <td>Signature: ______________________</td>
                                <td style="text-align: right;">Official Stamp</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Data Quality Audit (DQA) System &bull; Confidential Verification Dossier &bull; Generated: {{ now()->format('Y-m-d H:i') }} &bull; Page 2 of 2
    </div>
</body>
</html>
