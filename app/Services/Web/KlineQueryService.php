<?php

namespace App\Services\Web;

use App\Contracts\Repositories\KlineRepositoryInterface;
use App\Contracts\Services\KlineQueryServiceInterface;
use App\Contracts\Services\TechnicalIndicatorServiceInterface;

/**
 * Service for assembling kline chart data.
 *
 * Transforms raw kline records from the repository into OHLCV arrays
 * with Unix timestamps suitable for TradingView Lightweight Charts,
 * and optionally computes technical indicators.
 */
class KlineQueryService implements KlineQueryServiceInterface
{
    /**
     * Create a new kline query service instance.
     *
     * @param  \App\Contracts\Repositories\KlineRepositoryInterface  $klineRepository  Repository for kline data queries.
     * @param  \App\Contracts\Services\TechnicalIndicatorServiceInterface  $indicatorService  Service for computing technical indicators.
     */
    public function __construct(
        private KlineRepositoryInterface $klineRepository,
        private TechnicalIndicatorServiceInterface $indicatorService,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getChartKlines(int $tradingPairId, string $interval = '1m', array $indicators = []): array
    {
        $rawKlines = $this->klineRepository->getRecentKlinesForChart($tradingPairId, $interval);

        $klines = $rawKlines->map(function ($k) {
            $volume = (float) $k->volume;
            $takerBuyVolume = (float) $k->taker_buy_volume;
            $takerSellVolume = $volume > 0 ? $volume - $takerBuyVolume : 0;
            return [
                'time' => $k->open_time->timestamp,
                'open' => (float) $k->open,
                'high' => (float) $k->high,
                'low' => (float) $k->low,
                'close' => (float) $k->close,
                'volume' => $volume,
                'quote_volume' => (float) $k->quote_volume,
                'trade_count' => (int) $k->trade_count,
                'taker_buy_volume' => $takerBuyVolume,
                'taker_sell_volume' => round($takerSellVolume, 8),
            ];
        })->values()->all();

        $indicatorData = [];
        if (!empty($indicators) && $rawKlines->isNotEmpty()) {
            $indicatorData = $this->indicatorService->compute($rawKlines, $indicators);
        }

        return [
            'klines' => $klines,
            'indicators' => $indicatorData,
        ];
    }
}
