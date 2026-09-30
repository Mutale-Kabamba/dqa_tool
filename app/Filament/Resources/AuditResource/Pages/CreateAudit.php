<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use App\Services\DqaEngineService;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateAudit extends CreateRecord
{
    protected static string $resource = AuditResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!empty($data['audit_date'])) {
            $date = Carbon::parse($data['audit_date']);
            $data['period_month'] = $date->month;
            $data['period_quarter'] = $date->quarter;
            $data['period_year'] = $date->year;
            $data['period_label'] = $date->format('M-Y');
        }

        if (!empty($data['dimensions'])) {
            $engine = new DqaEngineService();
            $totalChecked = 0;
            $totalCompliant = 0;
            $priorities = [];

            foreach ($data['dimensions'] as $key => $dim) {
                $checked = (int) ($dim['checked_count'] ?? 0);
                $compliant = (int) ($dim['compliant_count'] ?? 0);
                $score = $checked > 0 ? round($compliant / $checked, 4) : 0.0;
                $status = $engine->computeStatus($score);

                $data['dimensions'][$key]['score_percentage'] = $score;
                $data['dimensions'][$key]['status'] = $status;

                $totalChecked += $checked;
                $totalCompliant += $compliant;

                if ($score < $engine->getGreenThreshold() && !empty($dim['dimension_name'])) {
                    $priorities[] = $dim['dimension_name'];
                }
            }

            $overallScore = $totalChecked > 0 ? round($totalCompliant / $totalChecked, 4) : 0.0;
            $data['overall_checked'] = $totalChecked;
            $data['overall_compliant'] = $totalCompliant;
            $data['overall_score'] = $overallScore;
            $data['overall_status'] = $engine->computeStatus($overallScore);
            $data['priority_areas'] = implode(', ', array_unique($priorities));
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
