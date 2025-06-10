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
    public function devices_endpoint_returns_successful_response(): void
    {
        $response = $this->get('/api/sync/devices');
        $response->assertStatus(200);
    }



    /** @test */
    public function apps_post_endpoint_accepts_new_fields(): void
    {
        $payload = [
            [
                'deviceId' => 'device1',
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

    /** @test */
    public function devices_post_endpoint_accepts_new_fields(): void
    {
        $payload = [
            [
                'deviceId' => 'abc123',
                'model' => 'Pixel',
                'batteryLevel' => 50,
            ]
        ];

        $response = $this->postJson('/api/sync/devices', $payload);
        $response->assertStatus(200);
    }

}
