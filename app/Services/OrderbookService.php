<?php

namespace App\Services;

use App\Models\Kline;
use App\Models\OrderbookHistory;
use App\Models\OrderbookSnapshot;
use App\Models\Ticker;
use App\Models\Trade;
use App\Models\TradingPair;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class OrderbookService
{
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

    public function cleanOldHistory(): array
    {
        $retentionHours = config('binance.history_retention_hours');
        $cutoff = now()->subHours($retentionHours);

        $historyDeleted = OrderbookHistory::where('received_at', '<', $cutoff)->delete();
        $tradesDeleted = Trade::where('traded_at', '<', $cutoff)->delete();
        $klinesDeleted = Kline::where('close_time', '<', $cutoff)->where('is_closed', true)->delete();

        return [
            'history' => $historyDeleted,
            'trades' => $tradesDeleted,
            'klines' => $klinesDeleted,
        ];
    }
}
