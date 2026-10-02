<?php

namespace App\Filament\Resources\CapaManagementResource\Pages;

use App\Filament\Resources\CapaManagementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCapaManagement extends CreateRecord
{
    protected static string $resource = CapaManagementResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
