<?php

namespace Tests\Feature\Admin;

use App\Models\FuturesMetricHistory;
use App\Models\LargeTrade;
use App\Models\OpenInterest;
use App\Models\OrderbookMetric;
use App\Models\OrderbookWall;
use App\Models\Trade;
use App\Models\TradeAggregate;
use App\Models\TradingPair;
use App\Models\VpinMetric;
use Tests\TestCase;

class AnalyticsDataTest extends TestCase
{
    public function test_returns_null_when_no_metrics(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->getJson(route('admin.trading-pairs.analytics-data', $pair));
        $response->assertStatus(200);
        $response->assertJson([]);
    }

    public function test_returns_full_chart_data(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookMetric::factory()->count(3)->create(['trading_pair_id' => $pair->id]);
        TradeAggregate::factory()->count(2)->sequence(
            fn($seq) => ['period_start' => now()->startOfMinute()->subMinutes($seq->index + 1)]
        )->create(['trading_pair_id' => $pair->id]);
        Trade::factory()->count(5)->create(['trading_pair_id' => $pair->id]);
        LargeTrade::factory()->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-data', $pair));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'bid_volume', 'ask_volume', 'imbalance', 'mid_price',
            'weighted_mid_price', 'spread_bps', 'received_at',
            'chart_metrics', 'chart_aggregates', 'chart_trades',
            'chart_volatility', 'large_trades',
            'chart_cumulative_cvd', 'chart_buy_sell_ratio',
            'orderbook_walls', 'vpin_value', 'chart_vpin',
        ]);

        $agg = $response->json('chart_aggregates.0');
        $this->assertArrayHasKey('avg_trade_size', $agg);
        $this->assertArrayHasKey('max_trade_size', $agg);
        $this->assertArrayHasKey('trade_count', $agg);
        $this->assertArrayHasKey('price_change_pct', $agg);
        $this->assertArrayHasKey('close_price', $agg);
    }

    public function test_large_trades_side_mapping(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookMetric::factory()->create(['trading_pair_id' => $pair->id]);
        LargeTrade::factory()->create(['trading_pair_id' => $pair->id, 'is_buyer_maker' => true]);
        LargeTrade::factory()->create(['trading_pair_id' => $pair->id, 'is_buyer_maker' => false]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-data', $pair));
        $response->assertStatus(200);
        $largeTrades = $response->json('large_trades');
        $sides = array_column($largeTrades, 'side');
        $this->assertContains('SELL', $sides);
        $this->assertContains('BUY', $sides);
    }

    public function test_null_volatility_filtered(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookMetric::factory()->create(['trading_pair_id' => $pair->id]);
        TradeAggregate::factory()->withoutVolatility()->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-data', $pair));
        $response->assertStatus(200);
        $this->assertEmpty($response->json('chart_volatility'));
    }

    public function test_cumulative_cvd_structure(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookMetric::factory()->create(['trading_pair_id' => $pair->id]);
        TradeAggregate::factory()->count(3)->sequence(
            fn($seq) => [
                'period_start' => now()->startOfMinute()->subMinutes($seq->index + 1),
                'cvd' => 100.0,
            ]
        )->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-data', $pair));
        $response->assertStatus(200);
        $cvd = $response->json('chart_cumulative_cvd');
        $this->assertCount(3, $cvd);
        $this->assertArrayHasKey('time', $cvd[0]);
        $this->assertArrayHasKey('value', $cvd[0]);
    }

    public function test_cumulative_cvd_accumulates(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookMetric::factory()->create(['trading_pair_id' => $pair->id]);
        TradeAggregate::factory()->count(3)->sequence(
            fn($seq) => [
                'period_start' => now()->startOfMinute()->subMinutes(3 - $seq->index),
                'cvd' => 10.0,
            ]
        )->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-data', $pair));
        $response->assertStatus(200);
        $cvd = $response->json('chart_cumulative_cvd');
        // Each subsequent value should increase by 10
        $this->assertGreaterThan($cvd[0]['value'], $cvd[1]['value']);
        $this->assertGreaterThan($cvd[1]['value'], $cvd[2]['value']);
    }

    public function test_buy_sell_ratio_structure(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookMetric::factory()->create(['trading_pair_id' => $pair->id]);
        TradeAggregate::factory()->create([
            'trading_pair_id' => $pair->id,
            'buy_volume' => 100.0,
            'sell_volume' => 50.0,
        ]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-data', $pair));
        $response->assertStatus(200);
        $ratio = $response->json('chart_buy_sell_ratio');
        $this->assertCount(1, $ratio);
        $this->assertArrayHasKey('time', $ratio[0]);
        $this->assertArrayHasKey('value', $ratio[0]);
        $this->assertEqualsWithDelta(2.0, $ratio[0]['value'], 0.01);
    }

    public function test_empty_cumulative_cvd_when_no_aggregates(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookMetric::factory()->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-data', $pair));
        $response->assertStatus(200);
        $this->assertEmpty($response->json('chart_cumulative_cvd'));
        $this->assertEmpty($response->json('chart_buy_sell_ratio'));
    }

    public function test_metrics_ordering(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookMetric::factory()->create(['trading_pair_id' => $pair->id, 'received_at' => now()->subMinutes(5)]);
        OrderbookMetric::factory()->create(['trading_pair_id' => $pair->id, 'received_at' => now()]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-data', $pair));
        $response->assertStatus(200);
        $metrics = $response->json('chart_metrics');
        $this->assertCount(2, $metrics);
    }

    public function test_regime_returns_structure(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookMetric::factory()->create(['trading_pair_id' => $pair->id]);
        TradeAggregate::factory()->count(5)->sequence(
            fn($seq) => ['period_start' => now()->startOfMinute()->subMinutes($seq->index + 1)]
        )->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-regime', $pair));
        $response->assertStatus(200);
        $response->assertJsonStructure(['regime', 'confidence', 'signals']);
        $this->assertContains($response->json('regime'), ['TRENDING_UP', 'TRENDING_DOWN', 'RANGING', 'VOLATILE']);
        $this->assertGreaterThanOrEqual(0, $response->json('confidence'));
        $this->assertLessThanOrEqual(1, $response->json('confidence'));
    }

    public function test_regime_defaults_to_ranging_without_data(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->getJson(route('admin.trading-pairs.analytics-regime', $pair));
        $response->assertStatus(200);
        $this->assertEquals('RANGING', $response->json('regime'));
        $this->assertEquals(0.0, $response->json('confidence'));
    }

    public function test_correlations_returns_structure(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->getJson(route('admin.trading-pairs.analytics-correlations', $pair));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'oi_vs_price', 'funding_vs_premium',
            'volume_vs_volatility', 'imbalance_vs_price',
        ]);
    }

    public function test_correlations_empty_without_data(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->getJson(route('admin.trading-pairs.analytics-correlations', $pair));
        $response->assertStatus(200);
        $this->assertEmpty($response->json('oi_vs_price'));
        $this->assertEmpty($response->json('funding_vs_premium'));
        $this->assertEmpty($response->json('volume_vs_volatility'));
        $this->assertEmpty($response->json('imbalance_vs_price'));
    }

    public function test_correlations_volume_vs_volatility(): void
    {
        $pair = TradingPair::factory()->create();
        TradeAggregate::factory()->count(3)->sequence(
            fn($seq) => [
                'period_start' => now()->startOfMinute()->subMinutes($seq->index + 1),
                'buy_volume' => 500.0,
                'sell_volume' => 300.0,
                'realized_vol_5m' => 0.01 + ($seq->index * 0.005),
            ]
        )->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-correlations', $pair));
        $response->assertStatus(200);
        $data = $response->json('volume_vs_volatility');
        $this->assertCount(3, $data);
        $this->assertArrayHasKey('x', $data[0]);
        $this->assertArrayHasKey('y', $data[0]);
    }

    public function test_correlations_funding_vs_premium(): void
    {
        $pair = TradingPair::factory()->create();
        FuturesMetricHistory::factory()->count(3)->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-correlations', $pair));
        $response->assertStatus(200);
        $data = $response->json('funding_vs_premium');
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('x', $data[0]);
        $this->assertArrayHasKey('y', $data[0]);
    }

    public function test_orderbook_walls_returned_in_chart_data(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookMetric::factory()->create(['trading_pair_id' => $pair->id]);
        OrderbookWall::factory()->create([
            'trading_pair_id' => $pair->id,
            'side' => 'BID',
            'status' => 'ACTIVE',
        ]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-data', $pair));
        $response->assertStatus(200);
        $walls = $response->json('orderbook_walls');
        $this->assertCount(1, $walls);
        $this->assertArrayHasKey('time', $walls[0]);
        $this->assertArrayHasKey('side', $walls[0]);
        $this->assertArrayHasKey('price', $walls[0]);
        $this->assertArrayHasKey('quantity', $walls[0]);
        $this->assertArrayHasKey('size_multiple', $walls[0]);
        $this->assertArrayHasKey('status', $walls[0]);
        $this->assertEquals('BID', $walls[0]['side']);
        $this->assertEquals('ACTIVE', $walls[0]['status']);
    }

    public function test_orderbook_walls_empty_when_none(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookMetric::factory()->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-data', $pair));
        $response->assertStatus(200);
        $this->assertEmpty($response->json('orderbook_walls'));
    }

    public function test_vpin_returned_in_chart_data(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookMetric::factory()->create(['trading_pair_id' => $pair->id]);
        VpinMetric::factory()->count(3)->sequence(
            fn($seq) => ['computed_at' => now()->subMinutes(3 - $seq->index)]
        )->create(['trading_pair_id' => $pair->id, 'vpin' => 0.45]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-data', $pair));
        $response->assertStatus(200);
        $this->assertEqualsWithDelta(0.45, $response->json('vpin_value'), 0.01);
        $chartVpin = $response->json('chart_vpin');
        $this->assertCount(3, $chartVpin);
        $this->assertArrayHasKey('time', $chartVpin[0]);
        $this->assertArrayHasKey('value', $chartVpin[0]);
    }

    public function test_vpin_null_when_no_records(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookMetric::factory()->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-data', $pair));
        $response->assertStatus(200);
        $this->assertNull($response->json('vpin_value'));
        $this->assertEmpty($response->json('chart_vpin'));
    }
}
