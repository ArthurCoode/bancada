<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** Ver a lista da equipe: só o dono. */
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    /** Adicionar membros: só o dono. */
    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Editar um membro: o dono, da MESMA loja, e nunca o próprio dono.
     */
    public function update(User $user, User $member): bool
    {
        return $user->isOwner()
            && $user->tenant_id === $member->tenant_id
            && ! $member->isOwner();
    }

    /**
     * Remover segue a mesma regra de editar. Como o dono não pode ser alvo,
     * ninguém consegue se remover por aqui.
     */
    public function delete(User $user, User $member): bool
    {
        return $this->update($user, $member);
    }
}