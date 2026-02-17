<?php

namespace Tests\Feature\Admin;

use App\Models\OrderbookMetric;
use App\Models\OrderbookSnapshot;
use App\Models\Ticker;
use App\Models\TradingPair;
use Tests\TestCase;

class DashboardDataTest extends TestCase
{
    public function test_returns_null_when_no_pairs(): void
    {
        $response = $this->getJson(route('admin.trading-pairs.index-data'));
        $response->assertStatus(200);
        $response->assertJson([]);
    }

    public function test_returns_full_data_with_snapshot_and_ticker(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookSnapshot::factory()->create(['trading_pair_id' => $pair->id]);
        Ticker::factory()->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.index-data'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'last_price', 'price_change', 'price_change_percent',
            'best_bid', 'best_ask', 'spread', 'spread_pct',
            'high_price', 'low_price', 'volume', 'trade_count',
            'last_update_at', 'update_id', 'received_at', 'sparkline',
            'quote_volume', 'weighted_avg_price', 'open_price',
        ]);
    }

    public function test_returns_data_without_snapshot(): void
    {
        $pair = TradingPair::factory()->create();
        Ticker::factory()->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.index-data'));
        $response->assertStatus(200);
        $response->assertJson(['best_bid' => null, 'best_ask' => null]);
    }

    public function test_returns_data_without_ticker(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookSnapshot::factory()->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.index-data'));
        $response->assertStatus(200);
        $response->assertJson(['price_change' => null, 'high_price' => null]);
    }

    public function test_sparkline_returns_float_values(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookSnapshot::factory()->create(['trading_pair_id' => $pair->id]);
        Ticker::factory()->create(['trading_pair_id' => $pair->id]);
        OrderbookMetric::factory()->count(5)->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.index-data'));
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(5, $data['sparkline']);
        foreach ($data['sparkline'] as $val) {
            $this->assertIsFloat($val);
        }
    }

    public function test_spread_pct_null_when_zero_bid(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookSnapshot::factory()->create([
            'trading_pair_id' => $pair->id,
            'best_bid_price' => 0,
            'best_ask_price' => 0.2151,
            'spread' => 0.2151,
            'bids' => [['0', '100']],
            'asks' => [['0.2151', '100']],
        ]);

        $response = $this->getJson(route('admin.trading-pairs.index-data'));
        $response->assertStatus(200);
        $this->assertNull($response->json('spread_pct'));
    }
}
