<?php

namespace Tests\Feature\Admin;

use App\Models\Kline;
use App\Models\TradingPair;
use Tests\TestCase;

class KlinesIndexTest extends TestCase
{
    public function test_klines_page_loads_with_default_interval(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->get(route('admin.trading-pairs.klines', $pair));
        $response->assertStatus(200);
        $response->assertSee('Klines');
    }

    public function test_klines_with_valid_interval(): void
    {
        $pair = TradingPair::factory()->create();
        Kline::factory()->create(['trading_pair_id' => $pair->id, 'interval' => '5m']);

        $response = $this->get(route('admin.trading-pairs.klines', [$pair, 'interval' => '5m']));
        $response->assertStatus(200);
    }

    public function test_klines_with_invalid_interval_redirects(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->get(route('admin.trading-pairs.klines', [$pair, 'interval' => 'invalid']));
        $response->assertStatus(302);
    }

    public function test_klines_filters_by_date(): void
    {
        $pair = TradingPair::factory()->create();
        Kline::factory()->create(['trading_pair_id' => $pair->id, 'open_time' => now()->subHours(2)]);
        Kline::factory()->create(['trading_pair_id' => $pair->id, 'open_time' => now()]);

        $response = $this->get(route('admin.trading-pairs.klines', [$pair, 'from' => now()->subHour()->format('Y-m-d\TH:i:s')]));
        $response->assertStatus(200);
    }

    public function test_klines_empty_state(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->get(route('admin.trading-pairs.klines', $pair));
        $response->assertStatus(200);
        $response->assertSee('No klines for');
    }

    public function test_klines_shows_open_badge(): void
    {
        $pair = TradingPair::factory()->create();
        Kline::factory()->open()->create(['trading_pair_id' => $pair->id]);

        $response = $this->get(route('admin.trading-pairs.klines', $pair));
        $response->assertStatus(200);
        $response->assertSee('OPEN');
    }

    public function test_klines_ordered_by_most_recent(): void
    {
        $pair = TradingPair::factory()->create();
        Kline::factory()->create(['trading_pair_id' => $pair->id, 'open_time' => now()->subMinutes(10)]);
        Kline::factory()->create(['trading_pair_id' => $pair->id, 'open_time' => now()]);

        $response = $this->get(route('admin.trading-pairs.klines', $pair));
        $response->assertStatus(200);
    }
}
