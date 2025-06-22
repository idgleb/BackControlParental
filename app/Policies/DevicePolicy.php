<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DevicePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Device $device): bool
    {
        return $device->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Device $device): bool
    {
        return $device->users()->where('user_id', $user->id)->exists();
    }
} 