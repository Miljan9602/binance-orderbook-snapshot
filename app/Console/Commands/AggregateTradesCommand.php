<?php

namespace App\Console\Commands;

use App\Services\OrderbookService;
use Illuminate\Console\Command;

class AggregateTradesCommand extends Command
{
    protected $signature = 'trades:aggregate';
    protected $description = 'Aggregate trades from the last minute into trade_aggregates';

    public function handle(OrderbookService $service): int
    {
        $count = $service->computeTradeAggregates();
        $this->info("Created/updated {$count} trade aggregate(s).");

        return Command::SUCCESS;
    }
}
