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


    /** @test */
    public function apps_post_endpoint_accepts_new_fields(): void
    {
        $payload = [
            [
                'packageName' => 'com.example.app',
                'appName' => 'Example',
                'appIcon' => base64_encode('icon'),
                'appCategory' => 'game',
                'contentRating' => 'E',
                'isSystemApp' => false,
                'usageTimeToday' => 0,
                'timeStempUsageTimeToday' => 0,
                'appStatus' => 'active',
                'dailyUsageLimitMinutes' => 60,
            ]
        ];

        $response = $this->postJson('/api/sync/apps', $payload);
        $response->assertStatus(200);
    }

}
