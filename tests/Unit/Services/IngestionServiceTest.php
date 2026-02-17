<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Kline;
use App\Models\LargeTrade;
use App\Models\OrderbookHistory;
use App\Models\OrderbookMetric;
use App\Models\OrderbookSnapshot;
use App\Models\Ticker;
use App\Models\Trade;
use App\Models\TradeAggregate;
use App\Models\TradingPair;
use App\Services\Ingestion\DataCleanupService;
use App\Services\Ingestion\KlineIngestionService;
use App\Services\Ingestion\LargeTradeDetector;
use App\Services\Ingestion\OrderbookIngestionService;
use App\Services\Ingestion\TickerIngestionService;
use App\Services\Ingestion\TradeAggregationService;
use App\Services\Ingestion\TradeIngestionService;
use Illuminate\Support\Carbon;

class IngestionServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // OrderbookIngestionService
    // -------------------------------------------------------------------------

    public function test_updateOrderbook_creates_snapshot_and_history(): void
    {
        $pair = TradingPair::factory()->create();

        $service = new OrderbookIngestionService();

        $data = [
            'bids' => [['0.2150', '100'], ['0.2149', '200']],
            'asks' => [['0.2151', '150'], ['0.2152', '250']],
            'lastUpdateId' => 123456,
        ];

        $service->updateOrderbook($pair->id, $data);

        $this->assertDatabaseHas('orderbook_snapshots', [
            'trading_pair_id' => $pair->id,
            'last_update_id' => 123456,
            'best_bid_price' => 0.2150,
            'best_ask_price' => 0.2151,
        ]);

        $this->assertDatabaseHas('orderbook_history', [
            'trading_pair_id' => $pair->id,
            'last_update_id' => 123456,
        ]);

        $this->assertEquals(1, OrderbookSnapshot::count());
        $this->assertEquals(1, OrderbookHistory::count());
    }

    public function test_updateOrderbook_upserts_existing_snapshot(): void
    {
        $pair = TradingPair::factory()->create();
        $service = new OrderbookIngestionService();

        $firstData = [
            'bids' => [['0.2150', '100']],
            'asks' => [['0.2151', '150']],
            'lastUpdateId' => 100,
        ];

        $secondData = [
            'bids' => [['0.2155', '200']],
            'asks' => [['0.2156', '300']],
            'lastUpdateId' => 200,
        ];

        $service->updateOrderbook($pair->id, $firstData);
        $service->updateOrderbook($pair->id, $secondData);

        // Snapshot should be upserted: still only one row, with updated values.
        $this->assertEquals(1, OrderbookSnapshot::count());

        $snapshot = OrderbookSnapshot::first();
        $this->assertEquals(200, $snapshot->last_update_id);
        $this->assertEquals('0.21550000', $snapshot->best_bid_price);

        // History is append-only: two rows.
        $this->assertEquals(2, OrderbookHistory::count());
    }

    public function test_updateOrderbook_calculates_spread(): void
    {
        $pair = TradingPair::factory()->create();
        $service = new OrderbookIngestionService();

        $data = [
            'bids' => [['0.2100', '100']],
            'asks' => [['0.2110', '150']],
            'lastUpdateId' => 1,
        ];

        $service->updateOrderbook($pair->id, $data);

        $snapshot = OrderbookSnapshot::first();
        // Spread = 0.2110 - 0.2100 = 0.0010
        $this->assertEqualsWithDelta(0.0010, (float) $snapshot->spread, 0.00001);
    }

    public function test_updateOrderbook_handles_empty_bids(): void
    {
        $pair = TradingPair::factory()->create();
        $service = new OrderbookIngestionService();

        $data = [
            'bids' => [],
            'asks' => [['0.2151', '150']],
            'lastUpdateId' => 1,
        ];

        $service->updateOrderbook($pair->id, $data);

        $snapshot = OrderbookSnapshot::first();
        $this->assertEquals('0.00000000', $snapshot->best_bid_price);
        $this->assertEquals('0.21510000', $snapshot->best_ask_price);
        // Spread = 0.2151 - 0 = 0.2151
        $this->assertEqualsWithDelta(0.2151, (float) $snapshot->spread, 0.00001);
    }

    public function test_updateOrderbook_computes_metrics_after_interval(): void
    {
        // Set a very short interval so metrics are always written.
        config(['binance.metrics_sample_interval' => 0]);

        $pair = TradingPair::factory()->create();
        $service = new OrderbookIngestionService();

        $data = [
            'bids' => [['0.2150', '100'], ['0.2149', '200']],
            'asks' => [['0.2152', '150'], ['0.2153', '250']],
            'lastUpdateId' => 1,
        ];

        $service->updateOrderbook($pair->id, $data);

        $this->assertEquals(1, OrderbookMetric::count());

        $metric = OrderbookMetric::first();
        $this->assertEquals($pair->id, $metric->trading_pair_id);
        // bid_volume = 100 + 200 = 300
        $this->assertEqualsWithDelta(300, (float) $metric->bid_volume, 0.01);
        // ask_volume = 150 + 250 = 400
        $this->assertEqualsWithDelta(400, (float) $metric->ask_volume, 0.01);
        // imbalance = (300 - 400) / 700 ~ -0.142857
        $this->assertEqualsWithDelta(-0.142857, (float) $metric->imbalance, 0.001);
        // mid_price = (0.2150 + 0.2152) / 2 = 0.2151
        $this->assertEqualsWithDelta(0.2151, (float) $metric->mid_price, 0.0001);
    }

    public function test_updateOrderbook_skips_metrics_within_interval(): void
    {
        // Set a long interval so the second call is within the interval.
        config(['binance.metrics_sample_interval' => 3600]);

        $pair = TradingPair::factory()->create();
        $service = new OrderbookIngestionService();

        $data = [
            'bids' => [['0.2150', '100']],
            'asks' => [['0.2152', '150']],
            'lastUpdateId' => 1,
        ];

        // First call: no lastMetricsWrite entry yet, defaults to 0, so interval check passes.
        $service->updateOrderbook($pair->id, $data);
        $this->assertEquals(1, OrderbookMetric::count());

        // Second call immediately: should be within the 3600s interval, so no new metric.
        $data['lastUpdateId'] = 2;
        $service->updateOrderbook($pair->id, $data);
        $this->assertEquals(1, OrderbookMetric::count());
    }

    // -------------------------------------------------------------------------
    // TradeIngestionService
    // -------------------------------------------------------------------------

    public function test_saveTrade_creates_trade(): void
    {
        $pair = TradingPair::factory()->create();

        $detector = $this->createMock(\App\Contracts\Services\LargeTradeDetectorInterface::class);
        $detector->expects($this->once())->method('evaluate');

        $service = new TradeIngestionService($detector);

        $tradeTime = now()->timestamp * 1000; // milliseconds
        $data = [
            'a' => 999888,
            'p' => '0.2150',
            'q' => '50.00',
            'f' => 1000,
            'l' => 1005,
            'm' => true,
            'T' => $tradeTime,
        ];

        $service->saveTrade($pair->id, $data);

        $this->assertDatabaseHas('trades', [
            'trading_pair_id' => $pair->id,
            'agg_trade_id' => 999888,
            'price' => 0.2150,
            'quantity' => 50.00,
            'first_trade_id' => 1000,
            'last_trade_id' => 1005,
            'is_buyer_maker' => true,
        ]);

        $this->assertEquals(1, Trade::count());
    }

    // -------------------------------------------------------------------------
    // TickerIngestionService
    // -------------------------------------------------------------------------

    public function test_updateTicker_creates_ticker(): void
    {
        $pair = TradingPair::factory()->create();
        $service = new TickerIngestionService();

        $data = [
            'p' => '0.0050',
            'P' => '2.38',
            'w' => '0.2125',
            'c' => '0.2150',
            'Q' => '25.00',
            'b' => '0.2149',
            'B' => '100.00',
            'a' => '0.2151',
            'A' => '150.00',
            'o' => '0.2100',
            'h' => '0.2200',
            'l' => '0.2050',
            'v' => '500000.00',
            'q' => '107500.00',
            'n' => 12345,
        ];

        $service->updateTicker($pair->id, $data);

        $this->assertDatabaseHas('tickers', [
            'trading_pair_id' => $pair->id,
            'last_price' => 0.2150,
            'trade_count' => 12345,
        ]);

        $this->assertEquals(1, Ticker::count());
    }

    public function test_updateTicker_upserts_existing(): void
    {
        $pair = TradingPair::factory()->create();
        $service = new TickerIngestionService();

        $data = [
            'p' => '0.0050',
            'P' => '2.38',
            'w' => '0.2125',
            'c' => '0.2150',
            'Q' => '25.00',
            'b' => '0.2149',
            'B' => '100.00',
            'a' => '0.2151',
            'A' => '150.00',
            'o' => '0.2100',
            'h' => '0.2200',
            'l' => '0.2050',
            'v' => '500000.00',
            'q' => '107500.00',
            'n' => 12345,
        ];

        $service->updateTicker($pair->id, $data);

        // Update with new price
        $data['c'] = '0.2200';
        $data['n'] = 13000;
        $service->updateTicker($pair->id, $data);

        $this->assertEquals(1, Ticker::count());

        $ticker = Ticker::first();
        $this->assertEquals('0.22000000', $ticker->last_price);
        $this->assertEquals(13000, $ticker->trade_count);
    }

    // -------------------------------------------------------------------------
    // KlineIngestionService
    // -------------------------------------------------------------------------

    public function test_updateKline_creates_kline(): void
    {
        $pair = TradingPair::factory()->create();
        $service = new KlineIngestionService();

        $openTimeMs = now()->startOfMinute()->timestamp * 1000;
        $closeTimeMs = now()->startOfMinute()->addMinute()->timestamp * 1000;

        $data = [
            'k' => [
                'i' => '1m',
                't' => $openTimeMs,
                'T' => $closeTimeMs,
                'o' => '0.2100',
                'h' => '0.2200',
                'l' => '0.2050',
                'c' => '0.2150',
                'v' => '10000.00',
                'q' => '2150.00',
                'V' => '5000.00',
                'Q' => '1075.00',
                'n' => 500,
                'x' => false,
            ],
        ];

        $service->updateKline($pair->id, $data);

        $this->assertDatabaseHas('klines', [
            'trading_pair_id' => $pair->id,
            'interval' => '1m',
            'open' => 0.2100,
            'close' => 0.2150,
            'is_closed' => false,
        ]);

        $this->assertEquals(1, Kline::count());
    }

    public function test_updateKline_upserts_existing(): void
    {
        $pair = TradingPair::factory()->create();
        $service = new KlineIngestionService();

        $openTimeMs = now()->startOfMinute()->timestamp * 1000;
        $closeTimeMs = now()->startOfMinute()->addMinute()->timestamp * 1000;

        $data = [
            'k' => [
                'i' => '1m',
                't' => $openTimeMs,
                'T' => $closeTimeMs,
                'o' => '0.2100',
                'h' => '0.2200',
                'l' => '0.2050',
                'c' => '0.2150',
                'v' => '10000.00',
                'q' => '2150.00',
                'V' => '5000.00',
                'Q' => '1075.00',
                'n' => 500,
                'x' => false,
            ],
        ];

        $service->updateKline($pair->id, $data);

        // Update with new close price and mark as closed.
        $data['k']['c'] = '0.2180';
        $data['k']['x'] = true;
        $service->updateKline($pair->id, $data);

        $this->assertEquals(1, Kline::count());

        $kline = Kline::first();
        $this->assertEquals('0.21800000', $kline->close);
        $this->assertTrue($kline->is_closed);
    }

    // -------------------------------------------------------------------------
    // LargeTradeDetector
    // -------------------------------------------------------------------------

    public function test_evaluate_does_nothing_below_window_minimum(): void
    {
        config(['binance.large_trade_window' => 100, 'binance.large_trade_multiplier' => 3]);

        $pair = TradingPair::factory()->create();
        $detector = new LargeTradeDetector();

        // Feed only 5 trades (below the minimum of 10).
        for ($i = 0; $i < 5; $i++) {
            $trade = Trade::factory()->create([
                'trading_pair_id' => $pair->id,
                'quantity' => 10.00,
            ]);
            $detector->evaluate($pair->id, $trade);
        }

        $this->assertEquals(0, LargeTrade::count());
    }

    public function test_evaluate_detects_large_trade(): void
    {
        config(['binance.large_trade_window' => 100, 'binance.large_trade_multiplier' => 3]);

        $pair = TradingPair::factory()->create();
        $detector = new LargeTradeDetector();

        // Seed the window with 15 normal-sized trades (qty = 10).
        for ($i = 0; $i < 15; $i++) {
            $trade = Trade::factory()->create([
                'trading_pair_id' => $pair->id,
                'quantity' => 10.00,
            ]);
            $detector->evaluate($pair->id, $trade);
        }

        // Now send a large trade: qty = 50, which is 5x average (10). Threshold is 3x.
        $largeTrade = Trade::factory()->create([
            'trading_pair_id' => $pair->id,
            'quantity' => 50.00,
            'price' => '0.2150',
        ]);
        $detector->evaluate($pair->id, $largeTrade);

        $this->assertEquals(1, LargeTrade::count());

        $detected = LargeTrade::first();
        $this->assertEquals($largeTrade->id, $detected->trade_id);
        $this->assertEquals('0.21500000', $detected->price);
        $this->assertGreaterThan(3, (float) $detected->size_multiple);
    }

    public function test_evaluate_ignores_normal_trade(): void
    {
        config(['binance.large_trade_window' => 100, 'binance.large_trade_multiplier' => 3]);

        $pair = TradingPair::factory()->create();
        $detector = new LargeTradeDetector();

        // Seed the window with 15 trades of size 10.
        for ($i = 0; $i < 15; $i++) {
            $trade = Trade::factory()->create([
                'trading_pair_id' => $pair->id,
                'quantity' => 10.00,
            ]);
            $detector->evaluate($pair->id, $trade);
        }

        // A trade of size 15 is only 1.5x the average -- below the 3x threshold.
        $normalTrade = Trade::factory()->create([
            'trading_pair_id' => $pair->id,
            'quantity' => 15.00,
        ]);
        $detector->evaluate($pair->id, $normalTrade);

        $this->assertEquals(0, LargeTrade::count());
    }

    public function test_evaluate_maintains_sliding_window(): void
    {
        config(['binance.large_trade_window' => 15, 'binance.large_trade_multiplier' => 3]);

        $pair = TradingPair::factory()->create();
        $detector = new LargeTradeDetector();

        // Fill the window (15) with trades of size 100 each.
        for ($i = 0; $i < 15; $i++) {
            $trade = Trade::factory()->create([
                'trading_pair_id' => $pair->id,
                'quantity' => 100.00,
            ]);
            $detector->evaluate($pair->id, $trade);
        }

        // Average is 100. A trade of 250 is 2.5x, below the 3x threshold.
        $borderTrade = Trade::factory()->create([
            'trading_pair_id' => $pair->id,
            'quantity' => 250.00,
        ]);
        $detector->evaluate($pair->id, $borderTrade);
        $this->assertEquals(0, LargeTrade::count());

        // After shifting the window, the 250 trade is in the window, raising average.
        // Now push more small trades to shift the window and lower the average.
        for ($i = 0; $i < 15; $i++) {
            $trade = Trade::factory()->create([
                'trading_pair_id' => $pair->id,
                'quantity' => 10.00,
            ]);
            $detector->evaluate($pair->id, $trade);
        }

        // Window is now all 10s. Average ~ 10. A trade of 50 is 5x > 3x threshold.
        $largeTrade = Trade::factory()->create([
            'trading_pair_id' => $pair->id,
            'quantity' => 50.00,
        ]);
        $detector->evaluate($pair->id, $largeTrade);
        $this->assertEquals(1, LargeTrade::count());
    }

    // -------------------------------------------------------------------------
    // TradeAggregationService
    // -------------------------------------------------------------------------

    public function test_computeTradeAggregates_creates_aggregate(): void
    {
        $pair = TradingPair::factory()->create();
        $service = new TradeAggregationService();

        $periodStart = now()->startOfMinute()->subMinute();

        // Create trades within the aggregation window.
        Trade::factory()->create([
            'trading_pair_id' => $pair->id,
            'price' => '0.2150',
            'quantity' => '100',
            'is_buyer_maker' => false,
            'traded_at' => $periodStart->copy()->addSeconds(10),
        ]);
        Trade::factory()->create([
            'trading_pair_id' => $pair->id,
            'price' => '0.2160',
            'quantity' => '200',
            'is_buyer_maker' => true,
            'traded_at' => $periodStart->copy()->addSeconds(30),
        ]);

        $created = $service->computeTradeAggregates();

        $this->assertEquals(1, $created);
        $this->assertEquals(1, TradeAggregate::count());

        $agg = TradeAggregate::first();
        $this->assertEquals($pair->id, $agg->trading_pair_id);
        $this->assertEquals('1m', $agg->interval);
        $this->assertEquals(2, $agg->trade_count);
    }

    public function test_computeTradeAggregates_skips_inactive_pairs(): void
    {
        $pair = TradingPair::factory()->inactive()->create();
        $service = new TradeAggregationService();

        $periodStart = now()->startOfMinute()->subMinute();
        Trade::factory()->create([
            'trading_pair_id' => $pair->id,
            'traded_at' => $periodStart->copy()->addSeconds(10),
        ]);

        $created = $service->computeTradeAggregates();

        $this->assertEquals(0, $created);
        $this->assertEquals(0, TradeAggregate::count());
    }

    public function test_computeTradeAggregates_skips_no_trades_period(): void
    {
        $pair = TradingPair::factory()->create();
        $service = new TradeAggregationService();

        // Create a trade outside the aggregation window (two minutes ago).
        Trade::factory()->create([
            'trading_pair_id' => $pair->id,
            'traded_at' => now()->subMinutes(5),
        ]);

        $created = $service->computeTradeAggregates();

        $this->assertEquals(0, $created);
        $this->assertEquals(0, TradeAggregate::count());
    }

    public function test_computeTradeAggregates_calculates_vwap(): void
    {
        $pair = TradingPair::factory()->create();
        $service = new TradeAggregationService();

        $periodStart = now()->startOfMinute()->subMinute();

        // Trade 1: price 100, qty 10 -> value 1000
        Trade::factory()->create([
            'trading_pair_id' => $pair->id,
            'price' => '100.0000',
            'quantity' => '10.00',
            'is_buyer_maker' => false,
            'traded_at' => $periodStart->copy()->addSeconds(5),
        ]);

        // Trade 2: price 200, qty 30 -> value 6000
        Trade::factory()->create([
            'trading_pair_id' => $pair->id,
            'price' => '200.0000',
            'quantity' => '30.00',
            'is_buyer_maker' => false,
            'traded_at' => $periodStart->copy()->addSeconds(15),
        ]);

        $service->computeTradeAggregates();

        $agg = TradeAggregate::first();
        // VWAP = (1000 + 6000) / (10 + 30) = 7000 / 40 = 175
        $this->assertEqualsWithDelta(175.0, (float) $agg->vwap, 0.01);
    }

    public function test_computeTradeAggregates_calculates_cvd(): void
    {
        $pair = TradingPair::factory()->create();
        $service = new TradeAggregationService();

        $periodStart = now()->startOfMinute()->subMinute();

        // Buy trade (is_buyer_maker = false): qty 100
        Trade::factory()->create([
            'trading_pair_id' => $pair->id,
            'price' => '0.2150',
            'quantity' => '100',
            'is_buyer_maker' => false,
            'traded_at' => $periodStart->copy()->addSeconds(5),
        ]);

        // Sell trade (is_buyer_maker = true): qty 60
        Trade::factory()->create([
            'trading_pair_id' => $pair->id,
            'price' => '0.2150',
            'quantity' => '60',
            'is_buyer_maker' => true,
            'traded_at' => $periodStart->copy()->addSeconds(15),
        ]);

        $service->computeTradeAggregates();

        $agg = TradeAggregate::first();
        // buy_volume = 100, sell_volume = 60, CVD = 100 - 60 = 40
        $this->assertEqualsWithDelta(100.0, (float) $agg->buy_volume, 0.01);
        $this->assertEqualsWithDelta(60.0, (float) $agg->sell_volume, 0.01);
        $this->assertEqualsWithDelta(40.0, (float) $agg->cvd, 0.01);
    }

    // -------------------------------------------------------------------------
    // DataCleanupService (spot)
    // -------------------------------------------------------------------------

    public function test_cleanSpotData_deletes_old_records(): void
    {
        config(['binance.history_retention_hours' => 24]);

        $pair = TradingPair::factory()->create();
        $oldTime = now()->subHours(25);

        OrderbookHistory::factory()->create([
            'trading_pair_id' => $pair->id,
            'received_at' => $oldTime,
        ]);
        Trade::factory()->create([
            'trading_pair_id' => $pair->id,
            'traded_at' => $oldTime,
        ]);
        Kline::factory()->create([
            'trading_pair_id' => $pair->id,
            'close_time' => $oldTime,
            'is_closed' => true,
        ]);
        OrderbookMetric::factory()->create([
            'trading_pair_id' => $pair->id,
            'received_at' => $oldTime,
        ]);
        TradeAggregate::factory()->create([
            'trading_pair_id' => $pair->id,
            'period_start' => $oldTime,
        ]);
        LargeTrade::factory()->create([
            'trading_pair_id' => $pair->id,
            'traded_at' => $oldTime,
        ]);

        $service = new DataCleanupService();
        $result = $service->cleanSpotData();

        $this->assertEquals(1, $result['history']);
        $this->assertEquals(1, $result['trades']);
        $this->assertEquals(1, $result['klines']);
        $this->assertEquals(1, $result['orderbook_metrics']);
        $this->assertEquals(1, $result['trade_aggregates']);
        $this->assertEquals(1, $result['large_trades']);

        $this->assertEquals(0, OrderbookHistory::count());
        $this->assertEquals(0, Trade::count());
        $this->assertEquals(0, Kline::count());
        $this->assertEquals(0, OrderbookMetric::count());
        $this->assertEquals(0, TradeAggregate::count());
        $this->assertEquals(0, LargeTrade::count());
    }

    public function test_cleanSpotData_preserves_recent_records(): void
    {
        config(['binance.history_retention_hours' => 24]);

        $pair = TradingPair::factory()->create();
        $recentTime = now()->subHours(1);

        OrderbookHistory::factory()->create([
            'trading_pair_id' => $pair->id,
            'received_at' => $recentTime,
        ]);
        Trade::factory()->create([
            'trading_pair_id' => $pair->id,
            'traded_at' => $recentTime,
        ]);
        Kline::factory()->create([
            'trading_pair_id' => $pair->id,
            'close_time' => $recentTime,
            'is_closed' => true,
        ]);
        OrderbookMetric::factory()->create([
            'trading_pair_id' => $pair->id,
            'received_at' => $recentTime,
        ]);
        TradeAggregate::factory()->create([
            'trading_pair_id' => $pair->id,
            'period_start' => $recentTime,
        ]);
        LargeTrade::factory()->create([
            'trading_pair_id' => $pair->id,
            'traded_at' => $recentTime,
        ]);

        $service = new DataCleanupService();
        $result = $service->cleanSpotData();

        $this->assertEquals(0, $result['history']);
        $this->assertEquals(0, $result['trades']);
        $this->assertEquals(0, $result['klines']);
        $this->assertEquals(0, $result['orderbook_metrics']);
        $this->assertEquals(0, $result['trade_aggregates']);
        $this->assertEquals(0, $result['large_trades']);

        $this->assertEquals(1, OrderbookHistory::count());
        $this->assertEquals(1, Trade::count());
        $this->assertEquals(1, Kline::count());
        $this->assertEquals(1, OrderbookMetric::count());
        $this->assertEquals(1, TradeAggregate::count());
        $this->assertEquals(1, LargeTrade::count());
    }
}
