<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToTenant, HasFactory;

    // tenant_id fica de fora: quem define é a trait, a partir do usuário logado.
    protected $fillable = ['name', 'phone', 'email', 'notes'];

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /** (11) 98765-4321 ou (11) 3456-7890, a partir dos dígitos guardados. */
    public function formattedPhone(): string
    {
        $digits = (string) $this->phone;

        return match (strlen($digits)) {
            11 => sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7)),
            10 => sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6)),
            default => $digits,
        };
    }
}