<?php

namespace App\Models;

use App\Enums\ServiceOrderStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceOrderStatusHistory extends Model
{
    use BelongsToTenant;

    // O histórico não tem updated_at: é um registro que nunca é alterado.
    public const UPDATED_AT = null;

    // Modelo interno: só o ServiceOrderService escreve aqui, nunca um formulário.
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'from_status' => ServiceOrderStatus::class,
            'to_status' => ServiceOrderStatus::class,
        ];
    }

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}