<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Toda consulta passa a ser filtrada pela loja do usuário logado.
        static::addGlobalScope(new TenantScope());

        // Ao criar, preenche o tenant_id sozinho (se não vier informado).
        static::creating(function (Model $model): void {
            if ($model->tenant_id === null && auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}