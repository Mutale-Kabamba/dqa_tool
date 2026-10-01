<?php

namespace App\Console\Commands;

use App\Services\DqaImportService;
use Illuminate\Console\Command;

class ImportDqaCsvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dqa:import {file : Path to the CSV file to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import batch field audit records from a standard CSV file';

    /**
     * Execute the console command.
     */
    public function handle(DqaImportService $importService): int
    {
        $file = $this->argument('file');
        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return Command::FAILURE;
        }

        $this->info("Importing audits from {$file}...");
        $result = $importService->importCsv($file);

        $this->info("Successfully imported/updated {$result['imported']} audits.");

        if (!empty($result['errors'])) {
            $this->warn("Warnings / Errors encountered:");
            foreach ($result['errors'] as $err) {
                $this->line(" - {$err}");
            }
        }

        return Command::SUCCESS;
    }
}
