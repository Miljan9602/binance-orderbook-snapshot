<?php

namespace App\Console\Commands;

use App\Services\OrderbookService;
use Illuminate\Console\Command;

class CleanOrderbookHistory extends Command
{
    protected $signature = 'orderbook:clean-history';
    protected $description = 'Clean old orderbook history, trades, and klines records';

    public function handle(OrderbookService $service): int
    {
        $deleted = $service->cleanOldHistory();
        $this->info("Cleaned {$deleted['history']} old orderbook history records.");
        $this->info("Cleaned {$deleted['trades']} old trade records.");
        $this->info("Cleaned {$deleted['klines']} old closed kline records.");

        return Command::SUCCESS;
    }
}
