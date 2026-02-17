<?php

namespace Tests\Feature\Admin;

use App\Models\OrderbookMetric;
use App\Models\TradeAggregate;
use App\Models\TradingPair;
use Tests\TestCase;

class DistributionsTest extends TestCase
{
    public function test_returns_structure(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->getJson(route('admin.trading-pairs.analytics-distributions', $pair));
        $response->assertStatus(200);
        $response->assertJsonStructure(['spread_histogram', 'hourly_stats']);
    }

    public function test_empty_histogram(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->getJson(route('admin.trading-pairs.analytics-distributions', $pair));
        $response->assertStatus(200);
        $this->assertEmpty($response->json('spread_histogram'));
    }

    public function test_spread_bucketing(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookMetric::factory()->create(['trading_pair_id' => $pair->id, 'spread_bps' => 0.5]);
        OrderbookMetric::factory()->create(['trading_pair_id' => $pair->id, 'spread_bps' => 1.5]);
        OrderbookMetric::factory()->create(['trading_pair_id' => $pair->id, 'spread_bps' => 1.8]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-distributions', $pair));
        $response->assertStatus(200);
        $histogram = $response->json('spread_histogram');
        $this->assertCount(8, $histogram);
        $bucket01 = collect($histogram)->firstWhere('bucket', '0-1');
        $this->assertEquals(1, $bucket01['count']);
        $bucket12 = collect($histogram)->firstWhere('bucket', '1-2');
        $this->assertEquals(2, $bucket12['count']);
    }

    public function test_hourly_stats_has_24_hours(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->getJson(route('admin.trading-pairs.analytics-distributions', $pair));
        $response->assertStatus(200);
        $this->assertCount(24, $response->json('hourly_stats'));
    }

    public function test_hourly_stats_structure(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->getJson(route('admin.trading-pairs.analytics-distributions', $pair));
        $response->assertStatus(200);
        $first = $response->json('hourly_stats.0');
        $this->assertArrayHasKey('hour', $first);
        $this->assertArrayHasKey('avg_volume', $first);
        $this->assertArrayHasKey('avg_trades', $first);
        $this->assertArrayHasKey('avg_spread', $first);
        $this->assertArrayHasKey('avg_imbalance', $first);
    }
}
