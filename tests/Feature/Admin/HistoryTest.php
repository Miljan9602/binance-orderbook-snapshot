<?php

namespace Tests\Feature\Admin;

use App\Models\OrderbookHistory;
use App\Models\TradingPair;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    public function test_history_page_loads(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->get(route('admin.trading-pairs.history', $pair));
        $response->assertStatus(200);
        $response->assertSee('History');
    }

    public function test_history_shows_records(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookHistory::factory()->count(3)->create(['trading_pair_id' => $pair->id]);

        $response = $this->get(route('admin.trading-pairs.history', $pair));
        $response->assertStatus(200);
        $response->assertSee('3 snapshots');
    }

    public function test_history_filters_by_from(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookHistory::factory()->create(['trading_pair_id' => $pair->id, 'received_at' => now()->subHours(2)]);
        OrderbookHistory::factory()->create(['trading_pair_id' => $pair->id, 'received_at' => now()]);

        $response = $this->get(route('admin.trading-pairs.history', [$pair, 'from' => now()->subHour()->toDateTimeString()]));
        $response->assertStatus(200);
        $response->assertSee('1 snapshots');
    }

    public function test_history_filters_by_min_spread(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookHistory::factory()->create(['trading_pair_id' => $pair->id, 'spread' => 0.0001]);
        OrderbookHistory::factory()->create(['trading_pair_id' => $pair->id, 'spread' => 0.001]);

        $response = $this->get(route('admin.trading-pairs.history', [$pair, 'min_spread' => 0.0005]));
        $response->assertStatus(200);
        $response->assertSee('1 snapshots');
    }

    public function test_history_filters_by_max_spread(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookHistory::factory()->create(['trading_pair_id' => $pair->id, 'spread' => 0.0001]);
        OrderbookHistory::factory()->create(['trading_pair_id' => $pair->id, 'spread' => 0.001]);

        $response = $this->get(route('admin.trading-pairs.history', [$pair, 'max_spread' => 0.0005]));
        $response->assertStatus(200);
        $response->assertSee('1 snapshots');
    }

    public function test_history_pagination(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookHistory::factory()->count(55)->create(['trading_pair_id' => $pair->id]);

        $response = $this->get(route('admin.trading-pairs.history', $pair));
        $response->assertStatus(200);
        $response->assertSee('55 snapshots');
    }

    public function test_history_empty_state(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->get(route('admin.trading-pairs.history', $pair));
        $response->assertStatus(200);
        $response->assertSee('No history records found');
    }

    public function test_history_ordered_by_most_recent(): void
    {
        $pair = TradingPair::factory()->create();
        $old = OrderbookHistory::factory()->create(['trading_pair_id' => $pair->id, 'received_at' => now()->subMinutes(10)]);
        $new = OrderbookHistory::factory()->create(['trading_pair_id' => $pair->id, 'received_at' => now()]);

        $response = $this->get(route('admin.trading-pairs.history', $pair));
        $response->assertStatus(200);
        $content = $response->getContent();
        $newPos = strpos($content, $new->received_at->format('Y-m-d H:i:s'));
        $oldPos = strpos($content, $old->received_at->format('Y-m-d H:i:s'));
        $this->assertLessThan($oldPos, $newPos);
    }

    public function test_history_combined_filters(): void
    {
        $pair = TradingPair::factory()->create();
        OrderbookHistory::factory()->create([
            'trading_pair_id' => $pair->id,
            'spread' => 0.0005,
            'received_at' => now()->subMinutes(5),
        ]);

        $response = $this->get(route('admin.trading-pairs.history', [
            $pair,
            'from' => now()->subHour()->toDateTimeString(),
            'min_spread' => 0.0003,
            'max_spread' => 0.001,
        ]));
        $response->assertStatus(200);
        $response->assertSee('1 snapshots');
    }
}
