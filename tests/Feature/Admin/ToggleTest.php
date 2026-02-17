<?php

namespace Tests\Feature\Admin;

use App\Models\TradingPair;
use Tests\TestCase;

class ToggleTest extends TestCase
{
    public function test_can_deactivate(): void
    {
        $pair = TradingPair::factory()->create(['is_active' => true]);

        $response = $this->post(route('admin.trading-pairs.toggle', $pair));
        $response->assertRedirect();
        $this->assertFalse($pair->fresh()->is_active);
    }

    public function test_can_activate(): void
    {
        $pair = TradingPair::factory()->inactive()->create();

        $response = $this->post(route('admin.trading-pairs.toggle', $pair));
        $response->assertRedirect();
        $this->assertTrue($pair->fresh()->is_active);
    }

    public function test_toggle_returns_404_for_nonexistent(): void
    {
        $response = $this->post('/admin/trading-pairs/99999/toggle');
        $response->assertStatus(404);
    }

    public function test_toggle_redirects_back(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->post(route('admin.trading-pairs.toggle', $pair));
        $response->assertRedirect();
        $response->assertSessionHas('status');
    }
}
