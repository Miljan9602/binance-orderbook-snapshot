<?php

namespace App\Repositories;

use App\Contracts\Repositories\TradeRepositoryInterface;
use App\DTOs\Filters\DateRangeFilter;
use App\Models\LargeTrade;
use App\Models\Trade;
use App\Models\TradeAggregate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repository for trade and trade aggregate data access.
 *
 * Queries trades, large trades, trade aggregates, and hourly
 * trade statistics used by the orderbook and analytics pages.
 *
 * @see \App\Contracts\Repositories\TradeRepositoryInterface
 */
class TradeRepository implements TradeRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getRecentTrades(int $tradingPairId, int $limit = 50): Collection
    {
        return Trade::forTradingPair($tradingPairId)
            ->orderByDesc('traded_at')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getRecentTradesForChart(int $tradingPairId, int $limit = 100): Collection
    {
        return Trade::forTradingPair($tradingPairId)
            ->orderByDesc('traded_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * {@inheritdoc}
     */
    public function getRecentLargeTrades(int $tradingPairId, int $limit = 20): Collection
    {
        return LargeTrade::forTradingPair($tradingPairId)
            ->orderByDesc('traded_at')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getRecentAggregates(int $tradingPairId, int $limit = 30): Collection
    {
        return TradeAggregate::forTradingPair($tradingPairId)
            ->orderByDesc('period_start')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * {@inheritdoc}
     */
    public function getFilteredAggregates(int $tradingPairId, DateRangeFilter $filter, int $perPage = 50): LengthAwarePaginator
    {
        $query = TradeAggregate::forTradingPair($tradingPairId);

        if ($filter->from !== null) {
            $query->where('period_start', '>=', $filter->from);
        }

        if ($filter->to !== null) {
            $query->where('period_start', '<=', $filter->to);
        }

        return $query->orderByDesc('period_start')
            ->paginate($perPage, ['*'], 'agg_page')
            ->withQueryString();
    }

    /**
     * {@inheritdoc}
     */
    public function getHourlyTradeStats(int $tradingPairId, int $hoursBack = 24): \Illuminate\Support\Collection
    {
        return DB::table('trade_aggregates')
            ->select(
                DB::raw("CAST(strftime('%H', period_start) AS INTEGER) as hour"),
                DB::raw('AVG(buy_volume + sell_volume) as avg_volume'),
                DB::raw('AVG(trade_count) as avg_trades')
            )
            ->where('trading_pair_id', $tradingPairId)
            ->where('interval', '1m')
            ->where('period_start', '>=', now()->subHours($hoursBack))
            ->groupByRaw("strftime('%H', period_start)")
            ->get()
            ->keyBy('hour');
    }

    /**
     * {@inheritdoc}
     */
    public function getCumulativeCvdBefore(int $tradingPairId, \Carbon\Carbon $before): float
    {
        return (float) TradeAggregate::forTradingPair($tradingPairId)
            ->where('interval', '1m')
            ->where('period_start', '<', $before)
            ->sum('cvd');
    }
}
