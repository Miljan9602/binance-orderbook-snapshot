<?php

namespace Tests\Feature\Admin;

use App\Models\TradingPair;
use Tests\TestCase;

class DashboardIndexTest extends TestCase
{
    public function test_dashboard_loads_with_no_pairs(): void
    {
        $response = $this->get(route('admin.trading-pairs.index'));
        $response->assertStatus(200);
        $response->assertSee('No trading pairs configured');
    }

    public function test_dashboard_loads_with_pair(): void
    {
        TradingPair::factory()->create();

        $response = $this->get(route('admin.trading-pairs.index'));
        $response->assertStatus(200);
        $response->assertSee('SEIUSDC');
    }

    public function test_dashboard_shows_live_badge(): void
    {
        TradingPair::factory()->create();

        $response = $this->get(route('admin.trading-pairs.index'));
        $response->assertStatus(200);
        $response->assertSee('LIVE');
    }
}
