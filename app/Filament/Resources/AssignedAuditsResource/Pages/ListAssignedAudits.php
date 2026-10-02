<?php

namespace App\Filament\Resources\AssignedAuditsResource\Pages;

use App\Filament\Resources\AssignedAuditsResource;
use Filament\Resources\Pages\ListRecords;

class ListAssignedAudits extends ListRecords
{
    protected static string $resource = AssignedAuditsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
