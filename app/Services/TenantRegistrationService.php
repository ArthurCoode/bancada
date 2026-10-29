<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantRegistrationService
{
    /**
     * Cria a loja e o usuário dono, juntos, em uma transação.
     */
    public function register(string $shopName, string $name, string $email, string $password): User
    {
        return DB::transaction(function () use ($shopName, $name, $email, $password) {
            $tenant = Tenant::create([
                'name' => $shopName,
                'slug' => $this->uniqueSlug($shopName),
            ]);

            $user = new User([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);
            // role e tenant_id não são mass assignable: definimos aqui, de forma explícita.
            $user->role = Role::Owner;

            $tenant->users()->save($user);

            return $user;
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'loja';
        $slug = $base;
        $i = 2;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}