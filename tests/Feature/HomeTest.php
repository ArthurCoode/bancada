<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeTest extends TestCase
{
    public function test_home_page_loads_with_app_name(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee(config('app.name'));
    }
}