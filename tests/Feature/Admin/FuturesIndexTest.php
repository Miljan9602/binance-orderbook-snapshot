<?php

namespace Tests\Feature\Admin;

use App\Models\FuturesMetricHistory;
use App\Models\OpenInterest;
use App\Models\TradingPair;
use Tests\TestCase;

class FuturesIndexTest extends TestCase
{
    public function test_futures_page_loads(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->get(route('admin.trading-pairs.futures', $pair));
        $response->assertStatus(200);
        $response->assertSee('Futures');
    }

    public function test_futures_page_without_futures_symbol(): void
    {
        $pair = TradingPair::factory()->withoutFutures()->create();

        $response = $this->get(route('admin.trading-pairs.futures', $pair));
        $response->assertStatus(200);
        $response->assertSee('No futures symbol configured');
    }

    public function test_futures_shows_history(): void
    {
        $pair = TradingPair::factory()->create();
        FuturesMetricHistory::factory()->count(3)->create(['trading_pair_id' => $pair->id]);

        $response = $this->get(route('admin.trading-pairs.futures', $pair));
        $response->assertStatus(200);
        $response->assertSee('3 records');
    }

    public function test_futures_shows_oi_history(): void
    {
        $pair = TradingPair::factory()->create();
        OpenInterest::factory()->count(2)->create(['trading_pair_id' => $pair->id]);

        $response = $this->get(route('admin.trading-pairs.futures', $pair));
        $response->assertStatus(200);
        $response->assertSee('2 records');
    }

    public function test_futures_filters_history_by_date(): void
    {
        $pair = TradingPair::factory()->create();
        FuturesMetricHistory::factory()->create(['trading_pair_id' => $pair->id, 'received_at' => now()->subHours(3)]);
        FuturesMetricHistory::factory()->create(['trading_pair_id' => $pair->id, 'received_at' => now()]);

        $response = $this->get(route('admin.trading-pairs.futures', [$pair, 'history_from' => now()->subHour()->format('Y-m-d\TH:i:s')]));
        $response->assertStatus(200);
    }
}
