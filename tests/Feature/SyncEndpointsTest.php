<?php

namespace Tests\Feature;

use Tests\TestCase;

class SyncEndpointsTest extends TestCase
{
    /** @test */
    public function apps_endpoint_returns_successful_response(): void
    {
        $response = $this->get('/api/sync/apps');
        $response->assertStatus(200);
    }

    /** @test */
    public function horarios_endpoint_returns_successful_response(): void
    {
        $response = $this->get('/api/sync/horarios');
        $response->assertStatus(200);
    }
}
