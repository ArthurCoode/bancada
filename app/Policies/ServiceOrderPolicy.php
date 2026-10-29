<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\ServiceOrder;
use App\Models\User;

class ServiceOrderPolicy
{
    /** A senha do aparelho só é vista por quem mexe no aparelho: dono e técnico. */
    public function viewDevicePassword(User $user, ServiceOrder $order): bool
    {
        return in_array($user->role, [Role::Owner, Role::Technician], true);
    }
}