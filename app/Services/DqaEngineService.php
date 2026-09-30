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
        $totalChecked = 0;
        $totalCompliant = 0;
        $priorityList = [];
        $dimensionRows = [];

        foreach ($dimensionsData as $dim => $counts) {
            $checked = (int) ($counts['checked'] ?? $counts['checked_count'] ?? 0);
            $compliant = (int) ($counts['compliant'] ?? $counts['compliant_count'] ?? 0);
            $score = $checked > 0 ? round($compliant / $checked, 4) : 0.0;
            $status = $this->computeStatus($score);

            if ($score < $this->greenThreshold) {
                $priorityList[] = $dim;
            }

            $totalChecked += $checked;
            $totalCompliant += $compliant;

            $dimensionRows[] = [
                'dimension_name'   => $dim,
                'checked_count'    => $checked,
                'compliant_count'  => $compliant,
                'score_percentage' => $score,
                'status'           => $status,
            ];
        }

        $overallScore = $totalChecked > 0 ? round($totalCompliant / $totalChecked, 4) : 0.0;
        $overallStatus = $this->computeStatus($overallScore);

        return [
            'overall_checked'   => $totalChecked,
            'overall_compliant' => $totalCompliant,
            'overall_score'     => $overallScore,
            'overall_status'    => $overallStatus,
            'priority_areas'    => implode(', ', $priorityList),
            'dimensions'        => $dimensionRows,
        ];
    }
}
