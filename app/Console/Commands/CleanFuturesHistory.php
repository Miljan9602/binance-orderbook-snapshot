<?php

namespace App\Console\Commands;

use App\Services\BinanceFuturesService;
use Illuminate\Console\Command;

class CleanFuturesHistory extends Command
{
    protected $signature = 'binance:clean-futures-history';
    protected $description = 'Clean old futures metrics history, liquidations, and open interest records';

    public function handle(BinanceFuturesService $service): int
    {
        $deleted = $service->cleanOldHistory();
        $this->info("Cleaned {$deleted['futures_history']} old futures metrics history records.");
        $this->info("Cleaned {$deleted['liquidations']} old liquidation records.");
        $this->info("Cleaned {$deleted['open_interest']} old open interest records.");

        return Command::SUCCESS;
    }
}
