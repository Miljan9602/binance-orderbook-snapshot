<?php

namespace App\Console\Commands;

use App\Contracts\Services\DataCleanupServiceInterface;
use Illuminate\Console\Command;

class CleanOrderbookHistory extends Command
{
    protected $signature = 'orderbook:clean-history';
    protected $description = 'Clean old orderbook history, trades, and klines records';

    public function handle(DataCleanupServiceInterface $service): int
    {
        $deleted = $service->cleanSpotData();
        $this->info("Cleaned {$deleted['history']} old orderbook history records.");
        $this->info("Cleaned {$deleted['trades']} old trade records.");
        $this->info("Cleaned {$deleted['klines']} old closed kline records.");
        $this->info("Cleaned {$deleted['orderbook_metrics']} old orderbook metrics records.");
        $this->info("Cleaned {$deleted['trade_aggregates']} old trade aggregate records.");
        $this->info("Cleaned {$deleted['large_trades']} old large trade records.");

        return Command::SUCCESS;
    }
}
