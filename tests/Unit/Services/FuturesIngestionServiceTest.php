<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\FuturesMetric;
use App\Models\FuturesMetricHistory;
use App\Models\Liquidation;
use App\Models\OpenInterest;
use App\Models\TradingPair;
use App\Services\Ingestion\DataCleanupService;
use App\Services\Ingestion\FuturesIngestionService;
use Illuminate\Support\Facades\Http;

class FuturesIngestionServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // FuturesIngestionService::updateMarkPrice
    // -------------------------------------------------------------------------

    public function test_updateMarkPrice_creates_metric(): void
    {
        config(['binance.metrics_sample_interval' => 0]);

        $pair = TradingPair::factory()->create();
        $service = new FuturesIngestionService();

        $nextFundingTimeMs = now()->addHours(4)->timestamp * 1000;

        $data = [
            'p' => '0.2155',
            'i' => '0.2150',
            'r' => '0.00010000',
            'T' => $nextFundingTimeMs,
        ];

        $service->updateMarkPrice($pair->id, $data);

        $this->assertDatabaseHas('futures_metrics', [
            'trading_pair_id' => $pair->id,
            'mark_price' => 0.2155,
            'index_price' => 0.2150,
            'funding_rate' => 0.00010000,
        ]);

        $this->assertEquals(1, FuturesMetric::count());
    }

    public function test_updateMarkPrice_upserts_existing(): void
    {
        config(['binance.metrics_sample_interval' => 3600]);

        $pair = TradingPair::factory()->create();
        $service = new FuturesIngestionService();

        $nextFundingTimeMs = now()->addHours(4)->timestamp * 1000;

        $data = [
            'p' => '0.2155',
            'i' => '0.2150',
            'r' => '0.00010000',
            'T' => $nextFundingTimeMs,
        ];

        $service->updateMarkPrice($pair->id, $data);

        // Update with new mark price.
        $data['p'] = '0.2200';
        $data['r'] = '0.00020000';
        $service->updateMarkPrice($pair->id, $data);

        // Should still be one row (upserted).
        $this->assertEquals(1, FuturesMetric::count());

        $metric = FuturesMetric::first();
        $this->assertEquals('0.22000000', $metric->mark_price);
        $this->assertEquals('0.0002000000', $metric->funding_rate);
    }

    public function test_updateMarkPrice_saves_history_after_interval(): void
    {
        // Set interval to 0 so history is always written.
        config(['binance.metrics_sample_interval' => 0]);

        $pair = TradingPair::factory()->create();
        $service = new FuturesIngestionService();

        $nextFundingTimeMs = now()->addHours(4)->timestamp * 1000;

        $data = [
            'p' => '0.2155',
            'i' => '0.2150',
            'r' => '0.00010000',
            'T' => $nextFundingTimeMs,
        ];

        $service->updateMarkPrice($pair->id, $data);

        $this->assertEquals(1, FuturesMetricHistory::count());

        $history = FuturesMetricHistory::first();
        $this->assertEquals($pair->id, $history->trading_pair_id);
        $this->assertEquals('0.21550000', $history->mark_price);
        $this->assertEquals('0.21500000', $history->index_price);
    }

    // -------------------------------------------------------------------------
    // FuturesIngestionService::saveLiquidation
    // -------------------------------------------------------------------------

    public function test_saveLiquidation_creates_record(): void
    {
        $pair = TradingPair::factory()->create();
        $service = new FuturesIngestionService();

        $orderTimeMs = now()->timestamp * 1000;

        $data = [
            'o' => [
                'S' => 'SELL',
                'o' => 'LIMIT',
                'p' => '0.2100',
                'q' => '5000.00',
                'ap' => '0.2098',
                'X' => 'FILLED',
                'T' => $orderTimeMs,
            ],
        ];

        $service->saveLiquidation($pair->id, $data);

        $this->assertDatabaseHas('liquidations', [
            'trading_pair_id' => $pair->id,
            'side' => 'SELL',
            'order_type' => 'LIMIT',
            'price' => 0.2100,
            'quantity' => 5000.00,
            'avg_price' => 0.2098,
            'order_status' => 'FILLED',
        ]);

        $this->assertEquals(1, Liquidation::count());
    }

    // -------------------------------------------------------------------------
    // FuturesIngestionService::fetchAndSaveOpenInterest
    // -------------------------------------------------------------------------

    public function test_fetchOpenInterest_success(): void
    {
        $pair = TradingPair::factory()->create([
            'futures_symbol' => 'seiusdt',
            'is_active' => true,
        ]);

        $apiTimestamp = now()->timestamp * 1000;

        Http::fake([
            config('binance.futures_rest_base_url') . '/fapi/v1/openInterest*' => Http::response([
                'openInterest' => '1500000.50',
                'time' => $apiTimestamp,
            ], 200),
        ]);

        $service = new FuturesIngestionService();
        $saved = $service->fetchAndSaveOpenInterest();

        $this->assertEquals(1, $saved);
        $this->assertEquals(1, OpenInterest::count());

        $oi = OpenInterest::first();
        $this->assertEquals($pair->id, $oi->trading_pair_id);
        $this->assertEqualsWithDelta(1500000.50, (float) $oi->open_interest, 0.01);
    }

    public function test_fetchOpenInterest_skips_inactive_pairs(): void
    {
        TradingPair::factory()->create([
            'futures_symbol' => 'seiusdt',
            'is_active' => false,
        ]);

        Http::fake();

        $service = new FuturesIngestionService();
        $saved = $service->fetchAndSaveOpenInterest();

        $this->assertEquals(0, $saved);
        $this->assertEquals(0, OpenInterest::count());

        Http::assertNothingSent();
    }

    public function test_fetchOpenInterest_skips_no_futures_symbol(): void
    {
        TradingPair::factory()->withoutFutures()->create([
            'is_active' => true,
        ]);

        Http::fake();

        $service = new FuturesIngestionService();
        $saved = $service->fetchAndSaveOpenInterest();

        $this->assertEquals(0, $saved);
        $this->assertEquals(0, OpenInterest::count());

        Http::assertNothingSent();
    }

    public function test_fetchOpenInterest_handles_api_failure(): void
    {
        TradingPair::factory()->create([
            'futures_symbol' => 'seiusdt',
            'is_active' => true,
        ]);

        Http::fake([
            config('binance.futures_rest_base_url') . '/fapi/v1/openInterest*' => Http::response(
                ['code' => -1121, 'msg' => 'Invalid symbol.'],
                400
            ),
        ]);

        $service = new FuturesIngestionService();
        $saved = $service->fetchAndSaveOpenInterest();

        $this->assertEquals(0, $saved);
        $this->assertEquals(0, OpenInterest::count());
    }

    // -------------------------------------------------------------------------
    // DataCleanupService (futures)
    // -------------------------------------------------------------------------

    public function test_cleanFuturesData_deletes_old_records(): void
    {
        config(['binance.history_retention_hours' => 24]);

        $pair = TradingPair::factory()->create();
        $oldTime = now()->subHours(25);

        FuturesMetricHistory::factory()->create([
            'trading_pair_id' => $pair->id,
            'received_at' => $oldTime,
        ]);
        Liquidation::factory()->create([
            'trading_pair_id' => $pair->id,
            'order_time' => $oldTime,
        ]);
        OpenInterest::factory()->create([
            'trading_pair_id' => $pair->id,
            'timestamp' => $oldTime,
        ]);

        $service = new DataCleanupService();
        $result = $service->cleanFuturesData();

        $this->assertEquals(1, $result['futures_history']);
        $this->assertEquals(1, $result['liquidations']);
        $this->assertEquals(1, $result['open_interest']);

        $this->assertEquals(0, FuturesMetricHistory::count());
        $this->assertEquals(0, Liquidation::count());
        $this->assertEquals(0, OpenInterest::count());
    }

    public function test_cleanFuturesData_preserves_recent_records(): void
    {
        config(['binance.history_retention_hours' => 24]);

        $pair = TradingPair::factory()->create();
        $recentTime = now()->subHours(1);

        FuturesMetricHistory::factory()->create([
            'trading_pair_id' => $pair->id,
            'received_at' => $recentTime,
        ]);
        Liquidation::factory()->create([
            'trading_pair_id' => $pair->id,
            'order_time' => $recentTime,
        ]);
        OpenInterest::factory()->create([
            'trading_pair_id' => $pair->id,
            'timestamp' => $recentTime,
        ]);

        $service = new DataCleanupService();
        $result = $service->cleanFuturesData();

        $this->assertEquals(0, $result['futures_history']);
        $this->assertEquals(0, $result['liquidations']);
        $this->assertEquals(0, $result['open_interest']);

        $this->assertEquals(1, FuturesMetricHistory::count());
        $this->assertEquals(1, Liquidation::count());
        $this->assertEquals(1, OpenInterest::count());
    }
}
