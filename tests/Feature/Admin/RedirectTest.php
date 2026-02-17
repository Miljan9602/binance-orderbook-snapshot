<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class RedirectTest extends TestCase
{
    public function test_root_redirects_to_dashboard(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('admin.trading-pairs.index'));
    }

    public function test_admin_redirects_to_dashboard(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect(route('admin.trading-pairs.index'));
    }
}
