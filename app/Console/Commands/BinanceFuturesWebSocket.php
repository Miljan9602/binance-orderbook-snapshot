<?php

namespace App\Console\Commands;

use App\Services\BinanceFuturesWebSocketService;
use Illuminate\Console\Command;

class BinanceFuturesWebSocket extends Command
{
    protected $signature = 'binance:futures-websocket';
    protected $description = 'Connect to Binance Futures WebSocket and stream mark price and liquidation data';

    public function handle(BinanceFuturesWebSocketService $service): int
    {
        $this->info('Starting Binance Futures WebSocket client...');
        $service->run();
        $this->info('Futures WebSocket client stopped.');

        return Command::SUCCESS;
    }
}
