<?php

namespace App\Filament\Resources\AuditSubmissionResource\Pages;

use App\Filament\Resources\AuditSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAuditSubmissions extends ListRecords
{
    protected static string $resource = AuditSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('+ Submit New Data File for Audit'),
        ];
    }
}
