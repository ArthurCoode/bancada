<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * Qualquer membro pode ver, cadastrar e editar clientes.
     * Só o dono remove. (O isolamento entre lojas já é garantido pelo escopo.)
     */
    public function delete(User $user, Customer $customer): bool
    {
        return $user->isOwner();
    }
}