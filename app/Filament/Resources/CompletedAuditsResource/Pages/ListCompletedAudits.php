<?php

namespace App\Filament\Resources\CompletedAuditsResource\Pages;

use App\Filament\Resources\CompletedAuditsResource;
use Filament\Resources\Pages\ListRecords;

class ListCompletedAudits extends ListRecords
{
    protected static string $resource = CompletedAuditsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
