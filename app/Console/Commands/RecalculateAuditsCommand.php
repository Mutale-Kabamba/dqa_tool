<?php

namespace App\Console\Commands;

use App\Services\DqaEngineService;
use Illuminate\Console\Command;

class RecalculateAuditsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dqa:recalculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate all audit dimension scores, overall scores, and RAG statuses against current threshold settings';

    /**
     * Execute the console command.
     */
    public function handle(DqaEngineService $engine): int
    {
        $this->info('Recalculating all audits against current RAG thresholds...');
        $count = $engine->recalculateAllAudits();
        $this->info("Successfully recalculated {$count} audits.");

        return Command::SUCCESS;
    }
}
