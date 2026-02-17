<?php

namespace App\Services\Ingestion;

use App\Contracts\Services\DataCleanupServiceInterface;
use App\Models\FuturesMetricHistory;
use App\Models\Kline;
use App\Models\LargeTrade;
use App\Models\Liquidation;
use App\Models\OpenInterest;
use App\Models\OrderbookHistory;
use App\Models\OrderbookMetric;
use App\Models\OrderbookWall;
use App\Models\Trade;
use App\Models\TradeAggregate;
use App\Models\VpinMetric;

/**
 * Service for cleaning old data based on retention policy.
 */
class DataCleanupService implements DataCleanupServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function cleanSpotData(): array
    {
        $retentionHours = config('binance.history_retention_hours');
        $cutoff = now()->subHours($retentionHours);

        return [
            'history' => OrderbookHistory::where('received_at', '<', $cutoff)->delete(),
            'trades' => Trade::where('traded_at', '<', $cutoff)->delete(),
            'klines' => Kline::where('close_time', '<', $cutoff)->where('is_closed', true)->delete(),
            'orderbook_metrics' => OrderbookMetric::where('received_at', '<', $cutoff)->delete(),
            'trade_aggregates' => TradeAggregate::where('period_start', '<', $cutoff)->delete(),
            'large_trades' => LargeTrade::where('traded_at', '<', $cutoff)->delete(),
            'orderbook_walls' => OrderbookWall::where('detected_at', '<', $cutoff)->delete(),
            'vpin_metrics' => VpinMetric::where('computed_at', '<', $cutoff)->delete(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function cleanFuturesData(): array
    {
        $retentionHours = config('binance.history_retention_hours');
        $cutoff = now()->subHours($retentionHours);

        return [
            'futures_history' => FuturesMetricHistory::where('received_at', '<', $cutoff)->delete(),
            'liquidations' => Liquidation::where('order_time', '<', $cutoff)->delete(),
            'open_interest' => OpenInterest::where('timestamp', '<', $cutoff)->delete(),
        ];
    }
}
