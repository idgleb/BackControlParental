<?php

namespace Tests\Unit;

use App\Models\Device;
use App\Models\DeviceApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_has_many_apps(): void
    {
        $device = Device::create([
            'deviceId' => 'abc',
            'model' => 'Pixel',
            'batteryLevel' => 100,
        ]);

        $device->apps()->create([
            'packageName' => 'com.example.app',
            'appName' => 'Example',
            'appIcon' => null,
            'appCategory' => 'game',
            'contentRating' => 'E',
            'isSystemApp' => false,
            'usageTimeToday' => 0,
            'timeStempUsageTimeToday' => 0,
            'appStatus' => 'active',
            'dailyUsageLimitMinutes' => 60,
        ]);

        $this->assertCount(1, $device->apps);
        $this->assertInstanceOf(Device::class, DeviceApp::first()->device);
    }

    public function test_deleting_device_removes_its_apps(): void
    {
        $device = Device::create([
            'deviceId' => 'xyz',
            'model' => 'Pixel',
            'batteryLevel' => 100,
        ]);

        $device->apps()->create([
            'packageName' => 'com.example.remove',
            'appName' => 'RemoveMe',
            'appIcon' => null,
            'appCategory' => 'game',
            'contentRating' => 'E',
            'isSystemApp' => false,
            'usageTimeToday' => 0,
            'timeStempUsageTimeToday' => 0,
            'appStatus' => 'active',
            'dailyUsageLimitMinutes' => 60,
        ]);

        $device->delete();

        $this->assertDatabaseMissing('device_apps', [
            'deviceId' => 'xyz',
            'packageName' => 'com.example.remove',
        ]);
    }
}
