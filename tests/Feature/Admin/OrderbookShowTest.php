<?php

namespace Tests\Feature\Admin;

use App\Models\OrderbookSnapshot;
use App\Models\Ticker;
use App\Models\TradingPair;
use Tests\TestCase;

class OrderbookShowTest extends TestCase
{
    public function test_show_page_loads(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookSnapshot::factory()->create(['trading_pair_id' => $pair->id]);

        $response = $this->get(route('admin.trading-pairs.show', $pair));
        $response->assertStatus(200);
        $response->assertSee($pair->symbol);
    }

    public function test_show_page_without_snapshot(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->get(route('admin.trading-pairs.show', $pair));
        $response->assertStatus(200);
    }

    public function test_show_page_displays_pair_info(): void
    {
        $pair = TradingPair::factory()->create();
        Ticker::factory()->create(['trading_pair_id' => $pair->id]);

        $response = $this->get(route('admin.trading-pairs.show', $pair));
        $response->assertStatus(200);
        $response->assertSee($pair->base_asset);
        $response->assertSee($pair->quote_asset);
    }

    public function test_show_returns_404_for_nonexistent(): void
    {
        $response = $this->get('/admin/trading-pairs/99999');
        $response->assertStatus(404);
    }

    public function test_show_has_navigation_links(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->get(route('admin.trading-pairs.show', $pair));
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('History');
        $response->assertSee('Analytics');
    }
}
