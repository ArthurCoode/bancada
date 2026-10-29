<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;

class DevicePolicy
{
    /** Qualquer membro cadastra e edita aparelhos. Só o dono remove. */
    public function delete(User $user, Device $device): bool
    {
        return $user->isOwner();
    }
}