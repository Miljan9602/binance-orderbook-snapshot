<?php

namespace Tests\Feature\Admin;

use App\Models\OrderbookMetric;
use App\Models\TradeAggregate;
use App\Models\TradingPair;
use Tests\TestCase;

class AnalyticsIndexTest extends TestCase
{
    public function test_analytics_page_loads(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->get(route('admin.trading-pairs.analytics', $pair));
        $response->assertStatus(200);
        $response->assertSee('Analytics');
    }

    public function test_analytics_shows_metrics(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookMetric::factory()->count(3)->create(['trading_pair_id' => $pair->id]);

        $response = $this->get(route('admin.trading-pairs.analytics', $pair));
        $response->assertStatus(200);
        $response->assertSee('3 records');
    }

    public function test_analytics_shows_aggregates(): void
    {
        $pair = TradingPair::factory()->create();
        TradeAggregate::factory()->count(2)->sequence(
            fn($seq) => ['period_start' => now()->startOfMinute()->subMinutes($seq->index + 1)]
        )->create(['trading_pair_id' => $pair->id]);

        $response = $this->get(route('admin.trading-pairs.analytics', $pair));
        $response->assertStatus(200);
    }

    public function test_analytics_empty_state(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->get(route('admin.trading-pairs.analytics', $pair));
        $response->assertStatus(200);
        $response->assertSee('No orderbook metrics yet');
    }

    public function test_analytics_filters_metrics_by_date(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookMetric::factory()->create(['trading_pair_id' => $pair->id, 'received_at' => now()->subHours(2)]);
        OrderbookMetric::factory()->create(['trading_pair_id' => $pair->id, 'received_at' => now()]);

        $response = $this->get(route('admin.trading-pairs.analytics', [$pair, 'metrics_from' => now()->subHour()->format('Y-m-d\TH:i:s')]));
        $response->assertStatus(200);
    }
}
