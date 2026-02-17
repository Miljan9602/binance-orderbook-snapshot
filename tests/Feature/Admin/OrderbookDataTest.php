<?php

namespace Tests\Feature\Admin;

use App\Models\OrderbookSnapshot;
use App\Models\Ticker;
use App\Models\Trade;
use App\Models\TradingPair;
use Tests\TestCase;

class OrderbookDataTest extends TestCase
{
    public function test_returns_full_data(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookSnapshot::factory()->create(['trading_pair_id' => $pair->id]);
        Ticker::factory()->create(['trading_pair_id' => $pair->id]);
        Trade::factory()->count(3)->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.show-data', $pair));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'snapshot' => ['bids', 'asks', 'best_bid_price', 'best_ask_price', 'spread'],
            'ticker' => ['last_price', 'price_change_percent', 'high_price', 'low_price', 'volume'],
            'trades',
        ]);
    }

    public function test_returns_null_snapshot_when_missing(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->getJson(route('admin.trading-pairs.show-data', $pair));
        $response->assertStatus(200);
        $response->assertJson(['snapshot' => null, 'ticker' => null]);
    }

    public function test_trades_have_correct_structure(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookSnapshot::factory()->create(['trading_pair_id' => $pair->id]);
        Trade::factory()->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.show-data', $pair));
        $response->assertStatus(200);
        $trades = $response->json('trades');
        $this->assertNotEmpty($trades);
        $this->assertArrayHasKey('price', $trades[0]);
        $this->assertArrayHasKey('quantity', $trades[0]);
        $this->assertArrayHasKey('is_buyer_maker', $trades[0]);
        $this->assertArrayHasKey('time', $trades[0]);
    }

    public function test_returns_404_for_nonexistent_pair(): void
    {
        $response = $this->getJson('/admin/trading-pairs/99999/data');
        $response->assertStatus(404);
    }
}
