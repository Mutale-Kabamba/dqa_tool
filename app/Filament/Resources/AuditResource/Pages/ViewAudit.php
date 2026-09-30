<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAudit extends ViewRecord
{
    protected static string $resource = AuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pdf')
                ->label('Download PDF Dossier')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->url(fn () => route('admin.audits.pdf', ['audit' => $this->record]))
                ->openUrlInNewTab(),
            Actions\Action::make('print')
                ->label('Print View')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->extraAttributes(['onclick' => 'window.print(); return false;']),
            Actions\EditAction::make(),
        ];
    }
}
