<?php

namespace App\Services;

use App\Models\Kline;
use App\Models\OrderbookHistory;
use App\Models\OrderbookMetric;
use App\Models\OrderbookSnapshot;
use App\Models\Ticker;
use App\Models\Trade;
use App\Models\TradeAggregate;
use App\Models\TradingPair;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderbookService
{
    private array $lastMetricsWrite = [];

    public function updateOrderbook(int $tradingPairId, array $data): void
    {
        $bids = $data['bids'] ?? [];
        $asks = $data['asks'] ?? [];
        $lastUpdateId = $data['lastUpdateId'] ?? 0;

        $bestBidPrice = !empty($bids) ? (float) $bids[0][0] : 0;
        $bestAskPrice = !empty($asks) ? (float) $asks[0][0] : 0;
        $spread = $bestAskPrice - $bestBidPrice;

        $now = now();

        $attributes = [
            'last_update_id' => $lastUpdateId,
            'bids' => $bids,
            'asks' => $asks,
            'best_bid_price' => $bestBidPrice,
            'best_ask_price' => $bestAskPrice,
            'spread' => $spread,
            'received_at' => $now,
        ];

        OrderbookSnapshot::updateOrCreate(
            ['trading_pair_id' => $tradingPairId],
            $attributes
        );

        TradingPair::where('id', $tradingPairId)->update(['last_update_at' => $now]);

        OrderbookHistory::create(array_merge(
            ['trading_pair_id' => $tradingPairId],
            $attributes
        ));

        $this->computeOrderbookMetrics($tradingPairId, $bids, $asks, $now);
    }

    public function saveTrade(int $tradingPairId, array $data): void
    {
        Trade::create([
            'trading_pair_id' => $tradingPairId,
            'agg_trade_id' => $data['a'],
            'price' => $data['p'],
            'quantity' => $data['q'],
            'first_trade_id' => $data['f'],
            'last_trade_id' => $data['l'],
            'is_buyer_maker' => $data['m'],
            'traded_at' => Carbon::createFromTimestampMs($data['T']),
            'created_at' => now(),
        ]);
    }

    public function updateTicker(int $tradingPairId, array $data): void
    {
        Ticker::updateOrCreate(
            ['trading_pair_id' => $tradingPairId],
            [
                'price_change' => $data['p'],
                'price_change_percent' => $data['P'],
                'weighted_avg_price' => $data['w'],
                'last_price' => $data['c'],
                'last_quantity' => $data['Q'],
                'best_bid_price' => $data['b'],
                'best_bid_quantity' => $data['B'],
                'best_ask_price' => $data['a'],
                'best_ask_quantity' => $data['A'],
                'open_price' => $data['o'],
                'high_price' => $data['h'],
                'low_price' => $data['l'],
                'volume' => $data['v'],
                'quote_volume' => $data['q'],
                'trade_count' => $data['n'],
                'received_at' => now(),
            ]
        );
    }

    public function updateKline(int $tradingPairId, array $data): void
    {
        $k = $data['k'];

        Kline::updateOrCreate(
            [
                'trading_pair_id' => $tradingPairId,
                'interval' => $k['i'],
                'open_time' => Carbon::createFromTimestampMs($k['t']),
            ],
            [
                'close_time' => Carbon::createFromTimestampMs($k['T']),
                'open' => $k['o'],
                'high' => $k['h'],
                'low' => $k['l'],
                'close' => $k['c'],
                'volume' => $k['v'],
                'quote_volume' => $k['q'],
                'taker_buy_volume' => $k['V'],
                'taker_buy_quote_volume' => $k['Q'],
                'trade_count' => $k['n'],
                'is_closed' => $k['x'],
                'received_at' => now(),
            ]
        );
    }

    private function computeOrderbookMetrics(int $tradingPairId, array $bids, array $asks, $now): void
    {
        $interval = config('binance.metrics_sample_interval');
        $lastWrite = $this->lastMetricsWrite[$tradingPairId] ?? 0;

        if (($now->timestamp - $lastWrite) < $interval) {
            return;
        }

        $bidVolume = 0;
        foreach ($bids as $bid) {
            $bidVolume += (float) $bid[1];
        }

        $askVolume = 0;
        foreach ($asks as $ask) {
            $askVolume += (float) $ask[1];
        }

        $totalVolume = $bidVolume + $askVolume;
        $imbalance = $totalVolume > 0 ? ($bidVolume - $askVolume) / $totalVolume : 0;

        $bestBidPrice = !empty($bids) ? (float) $bids[0][0] : 0;
        $bestAskPrice = !empty($asks) ? (float) $asks[0][0] : 0;
        $midPrice = ($bestBidPrice + $bestAskPrice) / 2;

        $bestBidQty = !empty($bids) ? (float) $bids[0][1] : 0;
        $bestAskQty = !empty($asks) ? (float) $asks[0][1] : 0;
        $totalBestQty = $bestBidQty + $bestAskQty;
        $weightedMidPrice = $totalBestQty > 0
            ? ($bestBidPrice * $bestAskQty + $bestAskPrice * $bestBidQty) / $totalBestQty
            : $midPrice;

        $spreadBps = $midPrice > 0 ? (($bestAskPrice - $bestBidPrice) / $midPrice) * 10000 : 0;

        OrderbookMetric::create([
            'trading_pair_id' => $tradingPairId,
            'bid_volume' => $bidVolume,
            'ask_volume' => $askVolume,
            'imbalance' => $imbalance,
            'mid_price' => $midPrice,
            'weighted_mid_price' => $weightedMidPrice,
            'spread_bps' => $spreadBps,
            'received_at' => $now,
        ]);

        $this->lastMetricsWrite[$tradingPairId] = $now->timestamp;
    }

    public function computeTradeAggregates(): int
    {
        $periodStart = now()->startOfMinute()->subMinute();
        $periodEnd = (clone $periodStart)->addMinute();

        $pairs = TradingPair::where('is_active', true)->get();
        $created = 0;

        foreach ($pairs as $pair) {
            $trades = Trade::where('trading_pair_id', $pair->id)
                ->where('traded_at', '>=', $periodStart)
                ->where('traded_at', '<', $periodEnd)
                ->get();

            if ($trades->isEmpty()) {
                continue;
            }

            $totalValue = 0;
            $totalQty = 0;
            $buyVolume = 0;
            $sellVolume = 0;
            $maxTradeSize = 0;

            foreach ($trades as $trade) {
                $price = (float) $trade->price;
                $qty = (float) $trade->quantity;
                $totalValue += $price * $qty;
                $totalQty += $qty;

                if ($trade->is_buyer_maker) {
                    $sellVolume += $qty;
                } else {
                    $buyVolume += $qty;
                }

                if ($qty > $maxTradeSize) {
                    $maxTradeSize = $qty;
                }
            }

            $vwap = $totalQty > 0 ? $totalValue / $totalQty : 0;
            $tradeCount = $trades->count();

            TradeAggregate::updateOrCreate(
                [
                    'trading_pair_id' => $pair->id,
                    'interval' => '1m',
                    'period_start' => $periodStart,
                ],
                [
                    'vwap' => $vwap,
                    'buy_volume' => $buyVolume,
                    'sell_volume' => $sellVolume,
                    'cvd' => $buyVolume - $sellVolume,
                    'trade_count' => $tradeCount,
                    'avg_trade_size' => $tradeCount > 0 ? $totalQty / $tradeCount : 0,
                    'max_trade_size' => $maxTradeSize,
                ]
            );

            $created++;
        }

        return $created;
    }

    public function cleanOldHistory(): array
    {
        $retentionHours = config('binance.history_retention_hours');
        $cutoff = now()->subHours($retentionHours);

        $historyDeleted = OrderbookHistory::where('received_at', '<', $cutoff)->delete();
        $tradesDeleted = Trade::where('traded_at', '<', $cutoff)->delete();
        $klinesDeleted = Kline::where('close_time', '<', $cutoff)->where('is_closed', true)->delete();
        $metricsDeleted = OrderbookMetric::where('received_at', '<', $cutoff)->delete();
        $aggregatesDeleted = TradeAggregate::where('period_start', '<', $cutoff)->delete();

        return [
            'history' => $historyDeleted,
            'trades' => $tradesDeleted,
            'klines' => $klinesDeleted,
            'orderbook_metrics' => $metricsDeleted,
            'trade_aggregates' => $aggregatesDeleted,
        ];
    }
}
