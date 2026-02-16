<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FuturesMetricHistory;
use App\Models\Kline;
use App\Models\Liquidation;
use App\Models\OpenInterest;
use App\Models\OrderbookHistory;
use App\Models\OrderbookMetric;
use App\Models\Trade;
use App\Models\TradeAggregate;
use App\Models\TradingPair;
use Illuminate\Http\Request;

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

    public function history(Request $request, TradingPair $tradingPair)
    {
        $query = OrderbookHistory::where('trading_pair_id', $tradingPair->id);

        if ($request->filled('from')) {
            $query->where('received_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('received_at', '<=', $request->input('to'));
        }

        if ($request->filled('min_spread')) {
            $query->where('spread', '>=', $request->input('min_spread'));
        }

        if ($request->filled('max_spread')) {
            $query->where('spread', '<=', $request->input('max_spread'));
        }

        $history = $query->orderByDesc('received_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.trading-pairs.history', compact('tradingPair', 'history'));
    }

    public function analytics(Request $request, TradingPair $tradingPair)
    {
        $metricsQuery = OrderbookMetric::where('trading_pair_id', $tradingPair->id);
        $aggregatesQuery = TradeAggregate::where('trading_pair_id', $tradingPair->id);

        if ($request->filled('metrics_from')) {
            $metricsQuery->where('received_at', '>=', $request->input('metrics_from'));
        }
        if ($request->filled('metrics_to')) {
            $metricsQuery->where('received_at', '<=', $request->input('metrics_to'));
        }

        if ($request->filled('agg_from')) {
            $aggregatesQuery->where('period_start', '>=', $request->input('agg_from'));
        }
        if ($request->filled('agg_to')) {
            $aggregatesQuery->where('period_start', '<=', $request->input('agg_to'));
        }

        $metrics = $metricsQuery->orderByDesc('received_at')->paginate(50, ['*'], 'metrics_page')->withQueryString();
        $aggregates = $aggregatesQuery->orderByDesc('period_start')->paginate(50, ['*'], 'agg_page')->withQueryString();

        return view('admin.trading-pairs.analytics', compact('tradingPair', 'metrics', 'aggregates'));
    }

    public function analyticsData(TradingPair $tradingPair)
    {
        $latest = OrderbookMetric::where('trading_pair_id', $tradingPair->id)
            ->orderByDesc('id')
            ->first();

        if (!$latest) {
            return response()->json(null);
        }

        // Recent metrics for charts (last 60 data points)
        $recentMetrics = OrderbookMetric::where('trading_pair_id', $tradingPair->id)
            ->orderByDesc('id')
            ->limit(60)
            ->get()
            ->reverse()
            ->values()
            ->map(fn($m) => [
                'time' => $m->received_at->format('H:i:s'),
                'imbalance' => (float) $m->imbalance,
                'mid_price' => (float) $m->mid_price,
                'spread_bps' => (float) $m->spread_bps,
                'bid_volume' => (float) $m->bid_volume,
                'ask_volume' => (float) $m->ask_volume,
            ]);

        // Recent trade aggregates for CVD chart (last 30)
        $recentAggregates = TradeAggregate::where('trading_pair_id', $tradingPair->id)
            ->orderByDesc('period_start')
            ->limit(30)
            ->get()
            ->reverse()
            ->values()
            ->map(fn($a) => [
                'time' => $a->period_start->format('H:i'),
                'cvd' => (float) $a->cvd,
                'buy_volume' => (float) $a->buy_volume,
                'sell_volume' => (float) $a->sell_volume,
                'vwap' => (float) $a->vwap,
            ]);

        return response()->json([
            'bid_volume' => (float) $latest->bid_volume,
            'ask_volume' => (float) $latest->ask_volume,
            'imbalance' => (float) $latest->imbalance,
            'mid_price' => (float) $latest->mid_price,
            'weighted_mid_price' => (float) $latest->weighted_mid_price,
            'spread_bps' => (float) $latest->spread_bps,
            'received_at' => $latest->received_at->format('H:i:s'),
            'chart_metrics' => $recentMetrics,
            'chart_aggregates' => $recentAggregates,
        ]);
    }

    public function futures(Request $request, TradingPair $tradingPair)
    {
        $historyQuery = FuturesMetricHistory::where('trading_pair_id', $tradingPair->id);
        $oiQuery = OpenInterest::where('trading_pair_id', $tradingPair->id);

        if ($request->filled('history_from')) {
            $historyQuery->where('received_at', '>=', $request->input('history_from'));
        }
        if ($request->filled('history_to')) {
            $historyQuery->where('received_at', '<=', $request->input('history_to'));
        }

        if ($request->filled('oi_from')) {
            $oiQuery->where('timestamp', '>=', $request->input('oi_from'));
        }
        if ($request->filled('oi_to')) {
            $oiQuery->where('timestamp', '<=', $request->input('oi_to'));
        }

        $history = $historyQuery->orderByDesc('received_at')->paginate(50, ['*'], 'history_page')->withQueryString();
        $oiHistory = $oiQuery->orderByDesc('timestamp')->paginate(50, ['*'], 'oi_page')->withQueryString();

        return view('admin.trading-pairs.futures', compact('tradingPair', 'history', 'oiHistory'));
    }

    public function futuresData(TradingPair $tradingPair)
    {
        $futuresMetric = $tradingPair->futuresMetric;
        $ticker = $tradingPair->ticker;
        $latestOi = OpenInterest::where('trading_pair_id', $tradingPair->id)->orderByDesc('id')->first();

        $liquidations = Liquidation::where('trading_pair_id', $tradingPair->id)
            ->orderByDesc('order_time')
            ->limit(50)
            ->get()
            ->map(fn($l) => [
                'side' => $l->side,
                'order_type' => $l->order_type,
                'price' => (float) $l->price,
                'quantity' => (float) $l->quantity,
                'avg_price' => (float) $l->avg_price,
                'order_status' => $l->order_status,
                'time' => $l->order_time->format('H:i:s'),
            ]);

        return response()->json([
            'futures' => $futuresMetric ? [
                'mark_price' => (float) $futuresMetric->mark_price,
                'index_price' => (float) $futuresMetric->index_price,
                'funding_rate' => (float) $futuresMetric->funding_rate,
                'next_funding_time' => $futuresMetric->next_funding_time?->toIso8601String(),
                'received_at' => $futuresMetric->received_at->format('H:i:s'),
            ] : null,
            'spot_price' => $ticker ? (float) $ticker->last_price : null,
            'open_interest' => $latestOi ? (float) $latestOi->open_interest : null,
            'liquidations' => $liquidations,
        ]);
    }

    public function klines(Request $request, TradingPair $tradingPair)
    {
        $interval = $request->input('interval', '1m');
        $allowedIntervals = ['1m', '5m', '15m', '1h'];
        if (!in_array($interval, $allowedIntervals)) {
            $interval = '1m';
        }

        $query = Kline::where('trading_pair_id', $tradingPair->id)
            ->where('interval', $interval);

        if ($request->filled('from')) {
            $query->where('open_time', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->where('open_time', '<=', $request->input('to'));
        }

        $klines = $query->orderByDesc('open_time')
            ->paginate(50)
            ->withQueryString();

        return view('admin.trading-pairs.klines', compact('tradingPair', 'klines', 'interval'));
    }

    public function toggle(TradingPair $tradingPair)
    {
        $tradingPair->update(['is_active' => !$tradingPair->is_active]);

        return back()->with('status', "{$tradingPair->symbol} " . ($tradingPair->is_active ? 'activated' : 'deactivated'));
    }
}
