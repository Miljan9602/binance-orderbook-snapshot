<?php

namespace App\Console\Commands;

use App\Services\BinanceWebSocketService;
use Illuminate\Console\Command;

class BinanceWebSocket extends Command
{
    protected $signature = 'binance:websocket';
    protected $description = 'Connect to Binance WebSocket and stream orderbook data';

    public function handle(BinanceWebSocketService $service): int
    {
        $this->info('Starting Binance WebSocket client...');
        $service->run();
        $this->info('WebSocket client stopped.');

        return Command::SUCCESS;
    }
}
