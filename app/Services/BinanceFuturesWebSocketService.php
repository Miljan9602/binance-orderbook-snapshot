<?php

namespace App\Services;

use App\Contracts\Services\FuturesIngestionServiceInterface;
use App\Models\TradingPair;
use Illuminate\Support\Facades\Log;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;

class BinanceFuturesWebSocketService
{
    private LoopInterface $loop;
    private FuturesIngestionServiceInterface $futuresService;
    private array $streamToTradingPairId = [];
    private int $reconnectDelay;
    private bool $shouldRun = true;

    public function __construct(FuturesIngestionServiceInterface $futuresService)
    {
        $this->futuresService = $futuresService;
        $this->reconnectDelay = config('binance.reconnect_base_delay');
        $this->loop = Loop::get();
    }

    public function run(): void
    {
        $activePairs = TradingPair::where('is_active', true)
            ->whereNotNull('futures_symbol')
            ->get();

        if ($activePairs->isEmpty()) {
            Log::warning('No active trading pairs with futures_symbol found. Exiting.');
            return;
        }

        $allStreams = [];

        foreach ($activePairs as $pair) {
            $symbol = $pair->futures_symbol;

            $markPriceStream = "{$symbol}@markPrice@1s";
            $forceOrderStream = "{$symbol}@forceOrder";

            $this->streamToTradingPairId[$markPriceStream] = $pair->id;
            $this->streamToTradingPairId[$forceOrderStream] = $pair->id;

            $allStreams[] = $markPriceStream;
            $allStreams[] = $forceOrderStream;
        }

        $streams = implode('/', $allStreams);
        $url = config('binance.futures_ws_base_url') . "/stream?streams={$streams}";

        Log::info("Connecting to Binance Futures WebSocket", [
            'url' => $url,
            'pairs' => $activePairs->pluck('futures_symbol')->toArray(),
        ]);

        $this->registerSignalHandlers();
        $this->connect($url);

        $this->loop->run();
    }

    private function connect(string $url): void
    {
        $connector = new Connector($this->loop);

        $connector($url)->then(
            function (WebSocket $conn) use ($url) {
                Log::info('Connected to Binance Futures WebSocket successfully');
                $this->reconnectDelay = config('binance.reconnect_base_delay');

                $conn->on('message', function (MessageInterface $msg) {
                    $this->handleMessage($msg);
                });

                $conn->on('close', function ($code = null, $reason = null) use ($url) {
                    Log::warning("Futures WebSocket connection closed", ['code' => $code, 'reason' => $reason]);
                    if ($this->shouldRun) {
                        $this->scheduleReconnect($url);
                    }
                });

                $conn->on('error', function (\Exception $e) {
                    Log::error("Futures WebSocket error", ['message' => $e->getMessage()]);
                });
            },
            function (\Exception $e) use ($url) {
                Log::error("Could not connect to Binance Futures WebSocket", ['message' => $e->getMessage()]);
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
            Log::warning('Invalid Futures WebSocket message received');
            return;
        }

        $streamName = $payload['stream'];
        $data = $payload['data'];

        $tradingPairId = $this->streamToTradingPairId[$streamName] ?? null;

        if ($tradingPairId === null) {
            Log::warning("Unknown futures stream received", ['stream' => $streamName]);
            return;
        }

        // Determine stream type
        $streamType = substr($streamName, strpos($streamName, '@') + 1);

        try {
            match (true) {
                str_starts_with($streamType, 'markPrice') => $this->futuresService->updateMarkPrice($tradingPairId, $data),
                $streamType === 'forceOrder' => $this->futuresService->saveLiquidation($tradingPairId, $data),
                default => Log::warning("Unhandled futures stream type", ['type' => $streamType]),
            };
        } catch (\Exception $e) {
            Log::error("Failed to process futures stream data", [
                'stream' => $streamName,
                'type' => $streamType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function scheduleReconnect(string $url): void
    {
        $delay = $this->reconnectDelay;
        Log::info("Futures reconnecting in {$delay} seconds...");

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
                Log::info('Received shutdown signal for futures. Closing gracefully...');
                $this->shouldRun = false;
                $this->loop->stop();
            });
        }
    }
}
