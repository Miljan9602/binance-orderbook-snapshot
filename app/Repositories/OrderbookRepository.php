<?php

namespace App\Repositories;

use App\Contracts\Repositories\OrderbookRepositoryInterface;
use App\DTOs\Filters\HistoryFilter;
use App\Models\OrderbookHistory;
use App\Models\OrderbookMetric;
use App\Models\OrderbookWall;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repository for orderbook data access.
 *
 * Queries orderbook snapshots, history, metrics, sparkline data,
 * spread analysis, and hourly statistics.
 *
 * @see \App\Contracts\Repositories\OrderbookRepositoryInterface
 */
class OrderbookRepository implements OrderbookRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getRecentSnapshots(int $tradingPairId, int $limit = 30): Collection
    {
        return OrderbookHistory::forTradingPair($tradingPairId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * {@inheritdoc}
     */
    public function getFilteredHistory(int $tradingPairId, HistoryFilter $filter, int $perPage = 50): LengthAwarePaginator
    {
        $query = OrderbookHistory::forTradingPair($tradingPairId);

        if ($filter->from !== null) {
            $query->where('received_at', '>=', $filter->from);
        }

        if ($filter->to !== null) {
            $query->where('received_at', '<=', $filter->to);
        }

        if ($filter->minSpread !== null) {
            $query->where('spread', '>=', $filter->minSpread);
        }

        if ($filter->maxSpread !== null) {
            $query->where('spread', '<=', $filter->maxSpread);
        }

        return $query->orderByDesc('received_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * {@inheritdoc}
     */
    public function getRecentMetrics(int $tradingPairId, int $limit = 60): Collection
    {
        return OrderbookMetric::forTradingPair($tradingPairId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * {@inheritdoc}
     */
    public function getLatestMetric(int $tradingPairId): ?OrderbookMetric
    {
        return OrderbookMetric::forTradingPair($tradingPairId)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getSparklineData(int $tradingPairId, int $limit = 30): \Illuminate\Support\Collection
    {
        return OrderbookMetric::forTradingPair($tradingPairId)
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('mid_price')
            ->reverse()
            ->values()
            ->map(fn($p) => (float) $p);
    }

    /**
     * {@inheritdoc}
     */
    public function getRecentSpreadBps(int $tradingPairId, int $limit = 500): \Illuminate\Support\Collection
    {
        return OrderbookMetric::forTradingPair($tradingPairId)
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('spread_bps')
            ->map(fn($v) => (float) $v);
    }

    /**
     * {@inheritdoc}
     */
    public function getHourlyOrderbookStats(int $tradingPairId, int $hoursBack = 24): \Illuminate\Support\Collection
    {
        return DB::table('orderbook_metrics')
            ->select(
                DB::raw("CAST(strftime('%H', received_at) AS INTEGER) as hour"),
                DB::raw('AVG(spread_bps) as avg_spread'),
                DB::raw('AVG(imbalance) as avg_imbalance')
            )
            ->where('trading_pair_id', $tradingPairId)
            ->where('received_at', '>=', now()->subHours($hoursBack))
            ->groupByRaw("strftime('%H', received_at)")
            ->get()
            ->keyBy('hour');
    }

    /**
     * {@inheritdoc}
     */
    public function getRecentWalls(int $tradingPairId, int $limit = 20): Collection
    {
        return OrderbookWall::forTradingPair($tradingPairId)
            ->orderByDesc('detected_at')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveWalls(int $tradingPairId): Collection
    {
        return OrderbookWall::forTradingPair($tradingPairId)
            ->active()
            ->orderByDesc('detected_at')
            ->get();
    }
}
