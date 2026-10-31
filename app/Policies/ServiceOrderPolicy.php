<?php

namespace App\Policies;

use App\Enums\Role;
use App\Enums\ServiceOrderStatus;
use App\Models\ServiceOrder;
use App\Models\User;

class ServiceOrderPolicy
{
    /** Status que o técnico pode aplicar: o trabalho de bancada. */
    private const TECHNICIAN_TARGETS = [
        ServiceOrderStatus::InAnalysis,
        ServiceOrderStatus::AwaitingApproval,
        ServiceOrderStatus::InRepair,
        ServiceOrderStatus::Ready,
    ];

    /** Status que o atendente pode aplicar: o contato com o cliente. */
    private const ATTENDANT_TARGETS = [
        ServiceOrderStatus::Approved,
        ServiceOrderStatus::Rejected,
        ServiceOrderStatus::Delivered,
    ];

    /** A senha do aparelho só é vista por quem mexe no aparelho: dono e técnico. */
    public function viewDevicePassword(User $user, ServiceOrder $order): bool
    {
        return in_array($user->role, [Role::Owner, Role::Technician], true);
    }

    /**
     * Este papel pode colocar a OS neste status? (O fluxo permitido, ou seja,
     * se a transição existe, é conferido à parte, no ServiceOrderService.)
     * Cancelar é exclusivo do dono.
     */
    public function changeStatus(User $user, ServiceOrder $order, ServiceOrderStatus $to): bool
    {
        return match ($user->role) {
            Role::Owner => true,
            Role::Technician => in_array($to, self::TECHNICIAN_TARGETS, true),
            Role::Attendant => in_array($to, self::ATTENDANT_TARGETS, true),
        };
    }
}