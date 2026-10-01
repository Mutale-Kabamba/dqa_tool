<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\AuditActionItem;
use App\Models\AuditDimension;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DqaImportService
{
    protected DqaEngineService $engine;

    public function __construct(DqaEngineService $engine)
    {
        $this->engine = $engine;
    }

    /**
     * Generate sample CSV template content.
     */
    public function generateCsvTemplate(): string
    {
        $headers = [
            'Audit Code',
            'Project Code',
            'Facility Name',
            'Auditor Name',
            'Audit Date (YYYY-MM-DD)',
            'Project Officer',
            'Accuracy Checked',
            'Accuracy Compliant',
            'Completeness Checked',
            'Completeness Compliant',
            'Consistency Checked',
            'Consistency Compliant',
            'Timeliness Checked',
            'Timeliness Compliant',
            'Validity Checked',
            'Validity Compliant',
            'Root Cause Notes',
            'Recommendations',
        ];

        $sampleRow = [
            'AUD-101',
            'SAMALANI-ANA',
            'Chilenje First Level Hospital',
            'M. Kabamba',
            now()->format('Y-m-d'),
            'J. Mwila (Project Officer)',
            '50',
            '47',
            '50',
            '45',
            '50',
            '48',
            '50',
            '38',
            '50',
            '46',
            'Delayed register update during month-end spike.',
            'Assign backup data clerk for month-end reconciliation.',
        ];

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);
        fputcsv($output, $sampleRow);
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

    /**
     * Process an uploaded CSV file path.
     *
     * @return array{imported: int, errors: array<string>}
     */
    public function importCsv(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return ['imported' => 0, 'errors' => ['File not found at specified path.']];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['imported' => 0, 'errors' => ['Unable to open CSV file for reading.']];
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return ['imported' => 0, 'errors' => ['The CSV file is empty.']];
        }

        // Normalize header strings
        $normalizedHeader = array_map(fn ($h) => strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $h))), $header);

        $importedCount = 0;
        $errors = [];
        $rowNumber = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                if (empty(array_filter($row))) {
                    continue; // Skip empty rows
                }

                $data = array_combine(array_slice($normalizedHeader, 0, count($row)), $row);

                $auditCode = trim($data['auditcode'] ?? $data['auditid'] ?? '');
                $projectCode = trim($data['projectcode'] ?? $data['project'] ?? '');
                $facilityName = trim($data['facilityname'] ?? $data['sitename'] ?? $data['site'] ?? '');
                $auditorName = trim($data['auditorname'] ?? $data['auditor'] ?? 'Field Auditor');
                $auditDateStr = trim($data['auditdateyyyy-mm-dd'] ?? $data['auditdate'] ?? $data['date'] ?? now()->format('Y-m-d'));
                $facilityInCharge = trim($data['projectofficer'] ?? $data['facilityincharge'] ?? $data['incharge'] ?? $data['officer'] ?? '');

                if (empty($auditCode)) {
                    $auditCode = 'AUD-' . str_pad((Audit::max('id') ?? 0) + 1, 3, '0', STR_PAD_LEFT);
                }

                $project = null;
                if (!empty($projectCode)) {
                    $project = Project::where('code', $projectCode)->orWhere('name', $projectCode)->first();
                }
                if (!$project) {
                    $project = Project::where('is_active', true)->first();
                }

                if (!$project) {
                    $errors[] = "Row {$rowNumber}: Project '{$projectCode}' could not be resolved and no active project exists.";
                    continue;
                }

                try {
                    $auditDate = Carbon::parse($auditDateStr);
                } catch (\Throwable $e) {
                    $auditDate = now();
                }

                $accChecked = max(0, (int) ($data['accuracychecked'] ?? 50));
                $accCompliant = min($accChecked, max(0, (int) ($data['accuracycompliant'] ?? 0)));

                $compChecked = max(0, (int) ($data['completenesschecked'] ?? 50));
                $compCompliant = min($compChecked, max(0, (int) ($data['completenesscompliant'] ?? 0)));

                $consChecked = max(0, (int) ($data['consistencychecked'] ?? 50));
                $consCompliant = min($consChecked, max(0, (int) ($data['consistencycompliant'] ?? 0)));

                $timeChecked = max(0, (int) ($data['timelinesschecked'] ?? 50));
                $timeCompliant = min($timeChecked, max(0, (int) ($data['timelinesscompliant'] ?? 0)));

                $valChecked = max(0, (int) ($data['validitychecked'] ?? 50));
                $valCompliant = min($valChecked, max(0, (int) ($data['validitycompliant'] ?? 0)));

                $dimensionsData = [
                    'Accuracy' => ['checked' => $accChecked, 'compliant' => $accCompliant],
                    'Completeness' => ['checked' => $compChecked, 'compliant' => $compCompliant],
                    'Consistency' => ['checked' => $consChecked, 'compliant' => $consCompliant],
                    'Timeliness' => ['checked' => $timeChecked, 'compliant' => $timeCompliant],
                    'Validity' => ['checked' => $valChecked, 'compliant' => $valCompliant],
                ];

                $calculated = $this->engine->computeAuditTotals($dimensionsData);

                $rootCauses = trim($data['rootcausenotes'] ?? $data['rootcauses'] ?? '');
                $recommendations = trim($data['recommendations'] ?? $data['actionplan'] ?? '');

                $audit = Audit::updateOrCreate(
                    ['audit_code' => $auditCode],
                    [
                        'project_id' => $project->id,
                        'site_name' => $facilityName ?: "Facility {$auditCode}",
                        'auditor_name' => $auditorName,
                        'audit_date' => $auditDate->format('Y-m-d'),
                        'period_month' => $auditDate->month,
                        'period_quarter' => $auditDate->quarter,
                        'period_year' => $auditDate->year,
                        'period_label' => $auditDate->format('M-Y'),
                        'overall_checked' => $calculated['overall_checked'],
                        'overall_compliant' => $calculated['overall_compliant'],
                        'overall_score' => $calculated['overall_score'],
                        'overall_status' => $calculated['overall_status'],
                        'priority_areas' => $calculated['priority_areas'],
                        'facility_in_charge' => $facilityInCharge,
                        'root_cause_notes' => $rootCauses,
                        'recommendations' => $recommendations,
                    ]
                );

                foreach ($calculated['dimensions'] as $dim) {
                    AuditDimension::updateOrCreate(
                        [
                            'audit_id' => $audit->id,
                            'dimension_name' => $dim['dimension_name'],
                        ],
                        [
                            'checked_count' => $dim['checked_count'],
                            'compliant_count' => $dim['compliant_count'],
                            'score_percentage' => $dim['score_percentage'],
                            'status' => $dim['status'],
                        ]
                    );
                }

                $importedCount++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $errors[] = "Import failed: " . $e->getMessage();
        } finally {
            fclose($handle);
        }

        return [
            'imported' => $importedCount,
            'errors' => $errors,
        ];
    }
}
