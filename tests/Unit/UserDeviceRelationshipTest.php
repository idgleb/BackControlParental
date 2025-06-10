<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeviceRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_many_devices(): void
    {
        $user = User::factory()->create();

        $deviceA = Device::create([
            'deviceId' => 'devA',
            'model' => 'Pixel',
            'batteryLevel' => 50,
        ]);

        $deviceB = Device::create([
            'deviceId' => 'devB',
            'model' => 'Samsung',
            'batteryLevel' => 80,
        ]);

        $user->devices()->attach([$deviceA->deviceId, $deviceB->deviceId]);

        $this->assertCount(2, $user->devices);
        $this->assertTrue($deviceA->users->contains($user));
    }
}
