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
            $dimData = [];
            foreach ($data['dimensions'] as $dim) {
                $name = $dim['dimension_name'] ?? 'Unknown';
                $dimData[$name] = [
                    'checked_count' => (int) ($dim['checked_count'] ?? 0),
                    'compliant_count' => (int) ($dim['compliant_count'] ?? 0),
                ];
            }

            $calculated = $engine->computeAuditTotals($dimData);

            $data['overall_checked'] = $calculated['overall_checked'];
            $data['overall_compliant'] = $calculated['overall_compliant'];
            $data['overall_score'] = $calculated['overall_score'];
            $data['overall_status'] = $calculated['overall_status'];
            $data['priority_areas'] = $calculated['priority_areas'];

            foreach ($data['dimensions'] as $key => $dim) {
                $name = $dim['dimension_name'] ?? '';
                foreach ($calculated['dimensions'] as $cDim) {
                    if ($cDim['dimension_name'] === $name) {
                        $data['dimensions'][$key]['score_percentage'] = $cDim['score_percentage'];
                        $data['dimensions'][$key]['status'] = $cDim['status'];
                    }
                }
            }
        }

        if (empty($data['workflow_status'])) {
            $data['workflow_status'] = !empty($data['auditor_id'])
                ? \App\Models\Audit::STATUS_ASSIGNED_TO_AUDITOR
                : \App\Models\Audit::STATUS_PENDING_ASSIGNMENT;
        }

        $user = auth()->user();
        if ($user && empty($data['project_officer_id']) && $user->isProjectOfficer() && !$user->isMealOfficer()) {
            $data['project_officer_id'] = $user->id;
            if (empty($data['facility_in_charge'])) {
                $data['facility_in_charge'] = $user->name;
            }
        }

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Step 1: Audit Data Submission Created (Pending Dispatch)';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
