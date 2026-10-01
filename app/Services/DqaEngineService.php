<?php

namespace App\Services;

use App\Models\Setting;

class DqaEngineService
{
    protected float $greenThreshold = 0.85;
    protected float $yellowThreshold = 0.70;
    protected float $orangeThreshold = 0.55;

    public function __construct()
    {
        try {
            $setting = Setting::current();
            if ($setting) {
                $this->greenThreshold = (float) $setting->green_threshold;
                $this->yellowThreshold = (float) $setting->yellow_threshold;
                $this->orangeThreshold = (float) $setting->orange_threshold;
            }
        } catch (\Throwable $e) {
            // Fallback to defaults if table or settings not yet migrated
        }
    }

    public function getGreenThreshold(): float
    {
        return $this->greenThreshold;
    }

    public function getYellowThreshold(): float
    {
        return $this->yellowThreshold;
    }

    public function getOrangeThreshold(): float
    {
        return $this->orangeThreshold;
    }

    public function computeStatus(float $score): string
    {
        if ($score >= $this->greenThreshold) {
            return 'GREEN';
        }
        if ($score >= $this->yellowThreshold) {
            return 'YELLOW';
        }
        if ($score >= $this->orangeThreshold) {
            return 'ORANGE';
        }
        return 'RED';
    }

    public function computeAuditTotals(array $dimensionsData): array
    {
        $checkedCounts = [];
        $compliantCounts = [];
        $scores = [];
        $priorityList = [];
        $dimensionRows = [];

        foreach ($dimensionsData as $dim => $counts) {
            $checked = max(0, (int) ($counts['checked'] ?? $counts['checked_count'] ?? 0));
            $compliant = max(0, (int) ($counts['compliant'] ?? $counts['compliant_count'] ?? 0));
            // Ensure compliant does not exceed checked
            $compliant = min($compliant, $checked);
            $score = $checked > 0 ? round($compliant / $checked, 4) : 0.0;
            $status = $this->computeStatus($score);

            if ($score < $this->greenThreshold) {
                $priorityList[] = $dim;
            }

            $checkedCounts[] = $checked;
            $compliantCounts[] = $compliant;
            if ($checked > 0) {
                $scores[] = $score;
            }

            $dimensionRows[] = [
                'dimension_name'   => $dim,
                'checked_count'    => $checked,
                'compliant_count'  => $compliant,
                'score_percentage' => $score,
                'status'           => $status,
            ];
        }

        // Unique records audited in the sampled visit (same records assessed across dimensions)
        $uniqueRecordsChecked = !empty($checkedCounts) ? max($checkedCounts) : 0;
        $overallScore = !empty($scores) ? round(array_sum($scores) / count($scores), 4) : 0.0;
        $overallCompliant = (int) round($uniqueRecordsChecked * $overallScore);
        $overallStatus = $this->computeStatus($overallScore);

        return [
            'overall_checked'   => $uniqueRecordsChecked,
            'overall_compliant' => $overallCompliant,
            'overall_score'     => $overallScore,
            'overall_status'    => $overallStatus,
            'priority_areas'    => implode(', ', $priorityList),
            'dimensions'        => $dimensionRows,
        ];
    }

    /**
     * Batch recalculate all audits and their dimensions against the active RAG thresholds.
     */
    public function recalculateAllAudits(): int
    {
        $audits = \App\Models\Audit::with('dimensions')->get();
        $count = 0;

        foreach ($audits as $audit) {
            $dimensionsData = [];
            foreach ($audit->dimensions as $dim) {
                $dimensionsData[$dim->dimension_name] = [
                    'checked_count' => $dim->checked_count,
                    'compliant_count' => $dim->compliant_count,
                ];
            }

            if (!empty($dimensionsData)) {
                $calculated = $this->computeAuditTotals($dimensionsData);

                $audit->update([
                    'overall_checked'   => $calculated['overall_checked'],
                    'overall_compliant' => $calculated['overall_compliant'],
                    'overall_score'     => $calculated['overall_score'],
                    'overall_status'    => $calculated['overall_status'],
                    'priority_areas'    => $calculated['priority_areas'],
                ]);

                foreach ($calculated['dimensions'] as $dimRow) {
                    $audit->dimensions()
                        ->where('dimension_name', $dimRow['dimension_name'])
                        ->update([
                            'score_percentage' => $dimRow['score_percentage'],
                            'status'           => $dimRow['status'],
                        ]);
                }

                $count++;
            }
        }

        return $count;
    }
}
