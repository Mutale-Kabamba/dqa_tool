<?php

namespace App\Filament\Resources\CapaManagementResource\Pages;

use App\Filament\Resources\CapaManagementResource;
use App\Filament\Widgets\CapaOverviewStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCapaManagement extends ListRecords
{
    protected static string $resource = CapaManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('+ Log New CAPA Item'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CapaOverviewStatsWidget::class,
        ];
    }
}
