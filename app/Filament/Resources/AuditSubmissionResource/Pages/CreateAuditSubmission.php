<?php

namespace App\Filament\Resources\AuditSubmissionResource\Pages;

use App\Filament\Resources\AuditSubmissionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAuditSubmission extends CreateRecord
{
    protected static string $resource = AuditSubmissionResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Data File Submitted Successfully (Pending Auditor Assignment)';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
