<?php

namespace App\Filament\Resources\CapaManagementResource\Pages;

use App\Filament\Resources\CapaManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCapaManagement extends EditRecord
{
    protected static string $resource = CapaManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
