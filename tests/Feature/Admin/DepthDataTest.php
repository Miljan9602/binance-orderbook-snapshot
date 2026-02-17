<?php

namespace Tests\Feature\Admin;

use App\Models\OrderbookHistory;
use App\Models\TradingPair;
use Tests\TestCase;

class DepthDataTest extends TestCase
{
    public function test_returns_empty_when_no_history(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->getJson(route('admin.trading-pairs.analytics-depth-data', $pair));
        $response->assertStatus(200);
        $response->assertJsonStructure(['timestamps', 'price_levels', 'bid_heat', 'ask_heat']);
        $this->assertEmpty($response->json('timestamps'));
    }

    public function test_returns_heatmap_structure(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookHistory::factory()->count(3)->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-depth-data', $pair));
        $response->assertStatus(200);
        $response->assertJsonStructure(['timestamps', 'price_levels', 'bid_heat', 'ask_heat', 'current_price']);
        $this->assertCount(3, $response->json('timestamps'));
    }

    public function test_current_price_computed(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookHistory::factory()->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-depth-data', $pair));
        $response->assertStatus(200);
        $this->assertNotNull($response->json('current_price'));
        $this->assertIsFloat($response->json('current_price'));
    }

    public function test_price_buckets_created(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookHistory::factory()->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-depth-data', $pair));
        $response->assertStatus(200);
        $this->assertCount(40, $response->json('price_levels'));
    }

    public function test_limits_to_30_snapshots(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookHistory::factory()->count(35)->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-depth-data', $pair));
        $response->assertStatus(200);
        $this->assertCount(30, $response->json('timestamps'));
    }

    public function test_handles_empty_bids(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookHistory::factory()->create([
            'trading_pair_id' => $pair->id,
            'bids' => [],
            'asks' => [['0.2151', '100']],
        ]);

        $response = $this->getJson(route('admin.trading-pairs.analytics-depth-data', $pair));
        $response->assertStatus(200);
    }
}
