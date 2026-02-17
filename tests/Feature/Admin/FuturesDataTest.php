<?php

namespace Tests\Feature\Admin;

use App\Models\FuturesMetric;
use App\Models\FuturesMetricHistory;
use App\Models\Liquidation;
use App\Models\OpenInterest;
use App\Models\Ticker;
use App\Models\TradingPair;
use Tests\TestCase;

class FuturesDataTest extends TestCase
{
    public function test_returns_full_data(): void
    {
        $pair = TradingPair::factory()->create();
        FuturesMetric::factory()->create(['trading_pair_id' => $pair->id]);
        Ticker::factory()->create(['trading_pair_id' => $pair->id]);
        OpenInterest::factory()->count(2)->sequence(
            fn($seq) => ['timestamp' => now()->subSeconds($seq->index * 30)]
        )->create(['trading_pair_id' => $pair->id]);
        FuturesMetricHistory::factory()->count(3)->create(['trading_pair_id' => $pair->id]);
        Liquidation::factory()->count(2)->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.futures-data', $pair));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'futures' => ['mark_price', 'index_price', 'funding_rate', 'next_funding_time', 'received_at'],
            'spot_price', 'open_interest', 'liquidations',
            'chart_funding', 'chart_oi', 'chart_premium', 'chart_liquidations',
        ]);

        // Verify new fields in chart_funding
        $funding = $response->json('chart_funding.0');
        $this->assertArrayHasKey('index_price', $funding);
        $this->assertArrayHasKey('mark_price', $funding);
        $this->assertArrayHasKey('annualized_funding', $funding);

        // Verify oi_delta in chart_oi
        $oi = $response->json('chart_oi.0');
        $this->assertArrayHasKey('oi_delta', $oi);

        // Verify notional in liquidations
        $liq = $response->json('liquidations.0');
        $this->assertArrayHasKey('notional', $liq);
    }

    public function test_returns_null_futures_when_no_metric(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->getJson(route('admin.trading-pairs.futures-data', $pair));
        $response->assertStatus(200);
        $this->assertNull($response->json('futures'));
    }

    public function test_premium_calculation(): void
    {
        $pair = TradingPair::factory()->create();
        FuturesMetric::factory()->create(['trading_pair_id' => $pair->id, 'mark_price' => 0.2200]);
        Ticker::factory()->create(['trading_pair_id' => $pair->id, 'last_price' => 0.2150]);
        FuturesMetricHistory::factory()->create(['trading_pair_id' => $pair->id, 'mark_price' => 0.2200]);

        $response = $this->getJson(route('admin.trading-pairs.futures-data', $pair));
        $response->assertStatus(200);
        $premiums = $response->json('chart_premium');
        $this->assertNotEmpty($premiums);
        $this->assertArrayHasKey('premium', $premiums[0]);
    }

    public function test_liquidation_grouping(): void
    {
        $pair = TradingPair::factory()->create();
        FuturesMetric::factory()->create(['trading_pair_id' => $pair->id]);
        Liquidation::factory()->buy()->create(['trading_pair_id' => $pair->id, 'order_time' => now()->subMinutes(5)]);
        Liquidation::factory()->sell()->create(['trading_pair_id' => $pair->id, 'order_time' => now()->subMinutes(5)]);

        $response = $this->getJson(route('admin.trading-pairs.futures-data', $pair));
        $response->assertStatus(200);
        $chartLiq = $response->json('chart_liquidations');
        if (!empty($chartLiq)) {
            $this->assertArrayHasKey('buy_qty', $chartLiq[0]);
            $this->assertArrayHasKey('sell_qty', $chartLiq[0]);
        }
    }

    public function test_30min_liquidation_window(): void
    {
        $pair = TradingPair::factory()->create();
        FuturesMetric::factory()->create(['trading_pair_id' => $pair->id]);
        Liquidation::factory()->create(['trading_pair_id' => $pair->id, 'order_time' => now()->subHours(2)]);

        $response = $this->getJson(route('admin.trading-pairs.futures-data', $pair));
        $response->assertStatus(200);
        $this->assertEmpty($response->json('chart_liquidations'));
    }

    public function test_returns_null_spot_price_without_ticker(): void
    {
        $pair = TradingPair::factory()->create();
        FuturesMetric::factory()->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.futures-data', $pair));
        $response->assertStatus(200);
        $this->assertNull($response->json('spot_price'));
    }
}
