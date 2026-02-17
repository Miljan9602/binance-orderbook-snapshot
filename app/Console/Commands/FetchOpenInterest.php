<?php

namespace App\Console\Commands;

use App\Contracts\Services\FuturesIngestionServiceInterface;
use Illuminate\Console\Command;

class FetchOpenInterest extends Command
{
    protected $signature = 'binance:fetch-open-interest';
    protected $description = 'Fetch open interest from Binance Futures REST API';

    public function handle(FuturesIngestionServiceInterface $service): int
    {
        $count = $service->fetchAndSaveOpenInterest();
        $this->info("Fetched open interest for {$count} pair(s).");

        return Command::SUCCESS;
    }
}
