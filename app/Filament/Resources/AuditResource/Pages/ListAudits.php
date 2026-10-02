<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAudits extends ListRecords
{
    protected static string $resource = AuditResource::class;

    protected function getHeaderActions(): array
    {
        $isMealOfficer = auth()->user()?->isMealOfficer() ?? false;

        if (! $isMealOfficer) {
            return [];
        }

        return [
            Actions\Action::make('download_template')
                ->label('CSV Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function () {
                    $service = app(\App\Services\DqaImportService::class);
                    $csv = $service->generateCsvTemplate();
                    return response()->streamDownload(function () use ($csv) {
                        echo $csv;
                    }, 'dqa_audit_batch_template.csv', [
                        'Content-Type' => 'text/csv',
                    ]);
                }),

            Actions\Action::make('import_csv')
                ->label('Import Batch Audits')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('csv_file')
                        ->label('Audit Batch CSV File')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->disk('local')
                        ->directory('imports')
                        ->required()
                        ->helperText('Upload a CSV file containing field audit data matching the standard DQA template.'),
                ])
                ->action(function (array $data) {
                    $filePath = storage_path('app/' . $data['csv_file']);
                    $service = app(\App\Services\DqaImportService::class);
                    $result = $service->importCsv($filePath);

                    if ($result['imported'] > 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('Batch Audits Imported')
                            ->body("Successfully imported/updated {$result['imported']} audits.")
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Import Completed with 0 Records')
                            ->body(implode("\n", $result['errors'] ?: ['No valid rows found.']))
                            ->warning()
                            ->send();
                    }
                }),

            Actions\CreateAction::make()
                ->label('+ New Audit Visit'),
        ];
    }
}
