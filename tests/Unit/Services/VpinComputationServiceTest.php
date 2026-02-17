<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Trade;
use App\Models\TradingPair;
use App\Models\VpinMetric;
use App\Repositories\TradeRepository;
use App\Repositories\TradingPairRepository;
use App\Services\Ingestion\VpinComputationService;

class VpinComputationServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // VpinComputationService — compute()
    // -------------------------------------------------------------------------

    public function test_compute_creates_vpin_metric(): void
    {
        config(['binance.vpin_bucket_count' => 5, 'binance.vpin_window' => 3]);

        $pair = TradingPair::factory()->create();

        // Create enough trades to fill at least 3 buckets.
        // Each trade has quantity 100, total = 2500. Bucket size = 2500 / 5 = 500.
        // 25 trades * 100 qty = 2500 total. Fills 5 buckets exactly.
        for ($i = 0; $i < 25; $i++) {
            Trade::factory()->create([
                'trading_pair_id' => $pair->id,
                'quantity' => 100,
                'is_buyer_maker' => $i % 2 === 0,
                'traded_at' => now()->subSeconds(25 - $i),
            ]);
        }

        $service = new VpinComputationService(
            new TradingPairRepository(),
            new TradeRepository(),
        );

        $service->compute($pair->id);

        $this->assertEquals(1, VpinMetric::count());

        $metric = VpinMetric::first();
        $this->assertEquals($pair->id, $metric->trading_pair_id);
        $this->assertEquals(5, $metric->bucket_count);
        $this->assertEquals(3, $metric->window_size);
        $this->assertNotNull($metric->computed_at);
        $this->assertGreaterThan(0, (float) $metric->vpin);
        $this->assertLessThanOrEqual(1, (float) $metric->vpin);
    }

    public function test_compute_skips_when_no_trades(): void
    {
        config(['binance.vpin_bucket_count' => 20, 'binance.vpin_window' => 10]);

        $pair = TradingPair::factory()->create();

        $service = new VpinComputationService(
            new TradingPairRepository(),
            new TradeRepository(),
        );

        $service->compute($pair->id);

        $this->assertEquals(0, VpinMetric::count());
    }

    public function test_compute_skips_when_not_enough_buckets(): void
    {
        // bucket_count = 5, window_size = 10. Since total volume is divided
        // into 5 buckets, we can never fill the required 10-bucket window.
        config(['binance.vpin_bucket_count' => 5, 'binance.vpin_window' => 10]);

        $pair = TradingPair::factory()->create();

        for ($i = 0; $i < 10; $i++) {
            Trade::factory()->create([
                'trading_pair_id' => $pair->id,
                'quantity' => 100,
                'traded_at' => now()->subSeconds(10 - $i),
            ]);
        }

        $service = new VpinComputationService(
            new TradingPairRepository(),
            new TradeRepository(),
        );

        $service->compute($pair->id);

        $this->assertEquals(0, VpinMetric::count());
    }

    public function test_compute_all_buys_gives_vpin_one(): void
    {
        config(['binance.vpin_bucket_count' => 5, 'binance.vpin_window' => 3]);

        $pair = TradingPair::factory()->create();

        // All trades are buys (is_buyer_maker = false).
        // Each bucket will have buy_vol = bucket_size, sell_vol = 0.
        // |buy - sell| / total = 1 for every bucket, so VPIN = 1.
        for ($i = 0; $i < 25; $i++) {
            Trade::factory()->create([
                'trading_pair_id' => $pair->id,
                'quantity' => 100,
                'is_buyer_maker' => false,
                'traded_at' => now()->subSeconds(25 - $i),
            ]);
        }

        $service = new VpinComputationService(
            new TradingPairRepository(),
            new TradeRepository(),
        );

        $service->compute($pair->id);

        $metric = VpinMetric::first();
        $this->assertEqualsWithDelta(1.0, (float) $metric->vpin, 0.0001);
    }

    public function test_compute_balanced_trades_gives_low_vpin(): void
    {
        config(['binance.vpin_bucket_count' => 4, 'binance.vpin_window' => 4]);

        $pair = TradingPair::factory()->create();

        // Alternating buy/sell of same size. Each bucket of 2 trades
        // will have buy_vol = sell_vol = 50, so |buy - sell| / total = 0.
        // Total = 8 * 50 = 400. Bucket size = 400 / 4 = 100. Each bucket gets 2 trades.
        for ($i = 0; $i < 8; $i++) {
            Trade::factory()->create([
                'trading_pair_id' => $pair->id,
                'quantity' => 50,
                'is_buyer_maker' => $i % 2 === 0,
                'traded_at' => now()->subSeconds(8 - $i),
            ]);
        }

        $service = new VpinComputationService(
            new TradingPairRepository(),
            new TradeRepository(),
        );

        $service->compute($pair->id);

        $metric = VpinMetric::first();
        $this->assertEqualsWithDelta(0.0, (float) $metric->vpin, 0.0001);
    }

    public function test_compute_stores_correct_bucket_volume(): void
    {
        config(['binance.vpin_bucket_count' => 5, 'binance.vpin_window' => 3]);

        $pair = TradingPair::factory()->create();

        // 10 trades of qty 50 = total 500. Bucket size = 500 / 5 = 100.
        for ($i = 0; $i < 10; $i++) {
            Trade::factory()->create([
                'trading_pair_id' => $pair->id,
                'quantity' => 50,
                'is_buyer_maker' => false,
                'traded_at' => now()->subSeconds(10 - $i),
            ]);
        }

        $service = new VpinComputationService(
            new TradingPairRepository(),
            new TradeRepository(),
        );

        $service->compute($pair->id);

        $metric = VpinMetric::first();
        // Bucket size = 500 / 5 = 100
        $this->assertEqualsWithDelta(100.0, (float) $metric->bucket_volume, 0.01);
    }

    // -------------------------------------------------------------------------
    // VpinComputationService — computeAll()
    // -------------------------------------------------------------------------

    public function test_computeAll_processes_active_pairs(): void
    {
        config(['binance.vpin_bucket_count' => 4, 'binance.vpin_window' => 2]);

        $activePair = TradingPair::factory()->create(['symbol' => 'SEIUSDC']);

        // 8 trades of qty 50 = 400. Bucket size = 400 / 4 = 100. 4 buckets >= window 2.
        for ($i = 0; $i < 8; $i++) {
            Trade::factory()->create([
                'trading_pair_id' => $activePair->id,
                'quantity' => 50,
                'is_buyer_maker' => false,
                'traded_at' => now()->subSeconds(8 - $i),
            ]);
        }

        $service = new VpinComputationService(
            new TradingPairRepository(),
            new TradeRepository(),
        );

        $service->computeAll();

        $this->assertEquals(1, VpinMetric::count());
        $this->assertEquals($activePair->id, VpinMetric::first()->trading_pair_id);
    }

    public function test_computeAll_skips_inactive_pairs(): void
    {
        config(['binance.vpin_bucket_count' => 4, 'binance.vpin_window' => 2]);

        $inactivePair = TradingPair::factory()->inactive()->create();

        for ($i = 0; $i < 8; $i++) {
            Trade::factory()->create([
                'trading_pair_id' => $inactivePair->id,
                'quantity' => 50,
                'is_buyer_maker' => false,
                'traded_at' => now()->subSeconds(8 - $i),
            ]);
        }

        $service = new VpinComputationService(
            new TradingPairRepository(),
            new TradeRepository(),
        );

        $service->computeAll();

        $this->assertEquals(0, VpinMetric::count());
    }

    // -------------------------------------------------------------------------
    // DataCleanupService — VPIN cleanup
    // -------------------------------------------------------------------------

    public function test_cleanSpotData_deletes_old_vpin_metrics(): void
    {
        config(['binance.history_retention_hours' => 24]);

        $pair = TradingPair::factory()->create();
        $oldTime = now()->subHours(25);

        VpinMetric::factory()->create([
            'trading_pair_id' => $pair->id,
            'computed_at' => $oldTime,
        ]);

        $service = new \App\Services\Ingestion\DataCleanupService();
        $result = $service->cleanSpotData();

        $this->assertEquals(1, $result['vpin_metrics']);
        $this->assertEquals(0, VpinMetric::count());
    }

    public function test_cleanSpotData_preserves_recent_vpin_metrics(): void
    {
        config(['binance.history_retention_hours' => 24]);

        $pair = TradingPair::factory()->create();
        $recentTime = now()->subHours(1);

        VpinMetric::factory()->create([
            'trading_pair_id' => $pair->id,
            'computed_at' => $recentTime,
        ]);

        $service = new \App\Services\Ingestion\DataCleanupService();
        $result = $service->cleanSpotData();

        $this->assertEquals(0, $result['vpin_metrics']);
        $this->assertEquals(1, VpinMetric::count());
    }

    // -------------------------------------------------------------------------
    // VpinMetric model
    // -------------------------------------------------------------------------

    public function test_vpin_metric_belongs_to_trading_pair(): void
    {
        $pair = TradingPair::factory()->create();
        $metric = VpinMetric::factory()->create(['trading_pair_id' => $pair->id]);

        $this->assertInstanceOf(TradingPair::class, $metric->tradingPair);
        $this->assertEquals($pair->id, $metric->tradingPair->id);
    }

    public function test_vpin_metric_scope_for_trading_pair(): void
    {
        $pair1 = TradingPair::factory()->create(['symbol' => 'SEIUSDC']);
        $pair2 = TradingPair::factory()->create(['symbol' => 'BTCUSDC']);

        VpinMetric::factory()->create(['trading_pair_id' => $pair1->id]);
        VpinMetric::factory()->create(['trading_pair_id' => $pair2->id]);

        $this->assertEquals(1, VpinMetric::forTradingPair($pair1->id)->count());
        $this->assertEquals(1, VpinMetric::forTradingPair($pair2->id)->count());
    }
}
