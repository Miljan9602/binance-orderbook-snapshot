<?php

namespace App\Services;

use App\Contracts\Services\KlineIngestionServiceInterface;
use App\Contracts\Services\OrderbookIngestionServiceInterface;
use App\Contracts\Services\TickerIngestionServiceInterface;
use App\Contracts\Services\TradeIngestionServiceInterface;
use App\Models\TradingPair;
use Illuminate\Support\Facades\Log;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;

class BinanceWebSocketService
{
    private LoopInterface $loop;
    private OrderbookIngestionServiceInterface $orderbookService;
    private TradeIngestionServiceInterface $tradeService;
    private TickerIngestionServiceInterface $tickerService;
    private KlineIngestionServiceInterface $klineService;
    private array $streamToTradingPairId = [];
    private int $reconnectDelay;
    private bool $shouldRun = true;

    public function __construct(
        OrderbookIngestionServiceInterface $orderbookService,
        TradeIngestionServiceInterface $tradeService,
        TickerIngestionServiceInterface $tickerService,
        KlineIngestionServiceInterface $klineService,
    ) {
        $this->orderbookService = $orderbookService;
        $this->tradeService = $tradeService;
        $this->tickerService = $tickerService;
        $this->klineService = $klineService;
        $this->reconnectDelay = config('binance.reconnect_base_delay');
        $this->loop = Loop::get();
    }

    private const ADDITIONAL_STREAMS = ['aggTrade', 'kline_1m', 'kline_5m', 'kline_15m', 'kline_1h', 'ticker'];

    public function run(): void
    {
        $activePairs = TradingPair::where('is_active', true)->get();

        if ($activePairs->isEmpty()) {
            Log::warning('No active trading pairs found. Exiting.');
            return;
        }

        $allStreams = [];

        foreach ($activePairs as $pair) {
            // Map the depth stream (existing)
            $this->streamToTradingPairId[$pair->stream_name] = $pair->id;
            $allStreams[] = $pair->stream_name;

            // Extract symbol prefix from stream_name (e.g., "seiusdc" from "seiusdc@depth20")
            $symbolPrefix = explode('@', $pair->stream_name)[0];

            // Add additional streams for this pair
            foreach (self::ADDITIONAL_STREAMS as $streamType) {
                $streamName = "{$symbolPrefix}@{$streamType}";
                $this->streamToTradingPairId[$streamName] = $pair->id;
                $allStreams[] = $streamName;
            }
        }

        $streams = implode('/', $allStreams);
        $url = config('binance.ws_base_url') . "/stream?streams={$streams}";

        Log::info("Connecting to Binance WebSocket", ['url' => $url, 'pairs' => $activePairs->pluck('symbol')->toArray()]);

        $this->registerSignalHandlers();
        $this->connect($url);

        $this->loop->run();
    }

    private function connect(string $url): void
    {
        $connector = new Connector($this->loop);

        $connector($url)->then(
            function (WebSocket $conn) use ($url) {
                Log::info('Connected to Binance WebSocket successfully');
                $this->reconnectDelay = config('binance.reconnect_base_delay');

                $conn->on('message', function (MessageInterface $msg) {
                    $this->handleMessage($msg);
                });

                $conn->on('close', function ($code = null, $reason = null) use ($url) {
                    Log::warning("WebSocket connection closed", ['code' => $code, 'reason' => $reason]);
                    if ($this->shouldRun) {
                        $this->scheduleReconnect($url);
                    }
                });

                $conn->on('error', function (\Exception $e) use ($url) {
                    Log::error("WebSocket error", ['message' => $e->getMessage()]);
                });
            },
            function (\Exception $e) use ($url) {
                Log::error("Could not connect to Binance WebSocket", ['message' => $e->getMessage()]);
                if ($this->shouldRun) {
                    $this->scheduleReconnect($url);
                }
            }
        );
    }

    private function handleMessage(MessageInterface $msg): void
    {
        $payload = json_decode((string) $msg, true);

        if (!$payload || !isset($payload['stream'], $payload['data'])) {
            Log::warning('Invalid WebSocket message received');
            return;
        }

        $streamName = $payload['stream'];
        $data = $payload['data'];

        $tradingPairId = $this->streamToTradingPairId[$streamName] ?? null;

        if ($tradingPairId === null) {
            Log::warning("Unknown stream received", ['stream' => $streamName]);
            return;
        }

        $streamType = substr($streamName, strpos($streamName, '@') + 1);

        try {
            match (true) {
                $streamType === 'depth20' => $this->orderbookService->updateOrderbook($tradingPairId, $data),
                $streamType === 'aggTrade' => $this->tradeService->saveTrade($tradingPairId, $data),
                $streamType === 'ticker' => $this->tickerService->updateTicker($tradingPairId, $data),
                str_starts_with($streamType, 'kline_') => $this->klineService->updateKline($tradingPairId, $data),
                default => Log::warning("Unhandled stream type", ['type' => $streamType]),
            };
        } catch (\Exception $e) {
            Log::error("Failed to process stream data", [
                'stream' => $streamName,
                'type' => $streamType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function scheduleReconnect(string $url): void
    {
        $delay = $this->reconnectDelay;
        Log::info("Reconnecting in {$delay} seconds...");

        $this->loop->addTimer($delay, function () use ($url) {
            if ($this->shouldRun) {
                $this->connect($url);
            }
        });

        $this->reconnectDelay = min(
            $this->reconnectDelay * 2,
            config('binance.reconnect_max_delay')
        );
    }

    private function registerSignalHandlers(): void
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        foreach ([SIGINT, SIGTERM] as $signal) {
            $this->loop->addSignal($signal, function () {
                Log::info('Received shutdown signal. Closing gracefully...');
                $this->shouldRun = false;
                $this->loop->stop();
            });
        }
    }
}
