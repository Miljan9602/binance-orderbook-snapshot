<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trade;
use App\Models\TradingPair;

class TradingPairController extends Controller
{
    public function index()
    {
        $pairs = TradingPair::with(['snapshot', 'ticker'])->orderBy('symbol')->get();

        return view('admin.trading-pairs.index', compact('pairs'));
    }

    public function indexData()
    {
        $pair = TradingPair::with(['snapshot', 'ticker'])->orderBy('symbol')->first();

        if (!$pair) {
            return response()->json(null);
        }

        $snapshot = $pair->snapshot;
        $ticker = $pair->ticker;

        return response()->json([
            'last_price' => $ticker ? (float) $ticker->last_price : ($snapshot ? (float) $snapshot->best_bid_price : null),
            'price_change' => $ticker ? (float) $ticker->price_change : null,
            'price_change_percent' => $ticker ? (float) $ticker->price_change_percent : null,
            'best_bid' => $snapshot ? (float) $snapshot->best_bid_price : null,
            'best_ask' => $snapshot ? (float) $snapshot->best_ask_price : null,
            'spread' => $snapshot ? (float) $snapshot->spread : null,
            'spread_pct' => $snapshot && (float) $snapshot->best_bid_price > 0
                ? ((float) $snapshot->spread / (float) $snapshot->best_bid_price) * 100 : null,
            'high_price' => $ticker ? (float) $ticker->high_price : null,
            'low_price' => $ticker ? (float) $ticker->low_price : null,
            'volume' => $ticker ? (float) $ticker->volume : null,
            'trade_count' => $ticker ? $ticker->trade_count : null,
            'last_update_at' => $pair->last_update_at?->diffForHumans(),
            'update_id' => $snapshot?->last_update_id,
            'received_at' => $snapshot?->received_at?->format('H:i:s'),
        ]);
    }

    public function show(TradingPair $tradingPair)
    {
        $tradingPair->load('snapshot');

        $recentTrades = Trade::where('trading_pair_id', $tradingPair->id)
            ->orderByDesc('traded_at')
            ->limit(50)
            ->get();

        $ticker = $tradingPair->ticker;

        return view('admin.trading-pairs.show', compact('tradingPair', 'recentTrades', 'ticker'));
    }

    public function showData(TradingPair $tradingPair)
    {
        $tradingPair->load('snapshot');
        $snapshot = $tradingPair->snapshot;
        $ticker = $tradingPair->ticker;

        $recentTrades = Trade::where('trading_pair_id', $tradingPair->id)
            ->orderByDesc('traded_at')
            ->limit(50)
            ->get()
            ->map(fn($t) => [
                'price' => (float) $t->price,
                'quantity' => (float) $t->quantity,
                'is_buyer_maker' => $t->is_buyer_maker,
                'time' => $t->traded_at->format('H:i:s'),
            ]);

        return response()->json([
            'snapshot' => $snapshot ? [
                'bids' => $snapshot->bids,
                'asks' => $snapshot->asks,
                'best_bid_price' => (float) $snapshot->best_bid_price,
                'best_ask_price' => (float) $snapshot->best_ask_price,
                'spread' => (float) $snapshot->spread,
                'last_update_id' => $snapshot->last_update_id,
                'received_at' => $snapshot->received_at->format('H:i:s'),
            ] : null,
            'ticker' => $ticker ? [
                'last_price' => (float) $ticker->last_price,
                'price_change_percent' => (float) $ticker->price_change_percent,
                'high_price' => (float) $ticker->high_price,
                'low_price' => (float) $ticker->low_price,
                'volume' => (float) $ticker->volume,
            ] : null,
            'trades' => $recentTrades,
        ]);
    }

    public function toggle(TradingPair $tradingPair)
    {
        $tradingPair->update(['is_active' => !$tradingPair->is_active]);

        return back()->with('status', "{$tradingPair->symbol} " . ($tradingPair->is_active ? 'activated' : 'deactivated'));
    }
}
