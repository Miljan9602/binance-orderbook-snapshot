<?php

namespace App\Console\Commands;

use App\Contracts\Services\VpinComputationServiceInterface;
use Illuminate\Console\Command;

class ComputeVpinCommand extends Command
{
    protected $signature = 'vpin:compute';
    protected $description = 'Compute VPIN for all active trading pairs';

    public function handle(VpinComputationServiceInterface $service): int
    {
        $service->computeAll();
        $this->info('VPIN computed successfully.');

        return Command::SUCCESS;
    }
}
