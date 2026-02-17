<?php

namespace Tests\Traits;

use App\Models\FuturesMetric;
use App\Models\FuturesMetricHistory;
use App\Models\Kline;
use App\Models\LargeTrade;
use App\Models\Liquidation;
use App\Models\OpenInterest;
use App\Models\OrderbookHistory;
use App\Models\OrderbookMetric;
use App\Models\OrderbookSnapshot;
use App\Models\Ticker;
use App\Models\Trade;
use App\Models\TradeAggregate;
use App\Models\TradingPair;

trait CreatesTestData
{
    protected function createTradingPairWithFullData(array $pairOverrides = []): TradingPair
    {
        $pair = TradingPair::factory()->create($pairOverrides);
        OrderbookSnapshot::factory()->create(['trading_pair_id' => $pair->id]);
        Ticker::factory()->create(['trading_pair_id' => $pair->id]);

        return $pair;
    }

    protected function createRealisticOrderbook(TradingPair $pair): OrderbookSnapshot
    {
        return OrderbookSnapshot::factory()->create(['trading_pair_id' => $pair->id]);
    }
}
