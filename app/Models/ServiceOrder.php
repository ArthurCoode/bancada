<?php

namespace App\Models;

use App\Enums\ServiceOrderStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOrder extends Model
{
    use BelongsToTenant, HasFactory;

    /** Itens oficiais do checklist de entrada (chave => rótulo). */
    public const CHECKLIST_ITEMS = [
        'screen' => 'Tela',
        'touch' => 'Touch',
        'buttons' => 'Botões',
        'camera' => 'Câmeras',
        'audio' => 'Áudio (alto-falante e microfone)',
        'charging' => 'Conector de carga',
        'wireless' => 'Wi-Fi e Bluetooth',
        'sim' => 'Chip e sinal',
        'liquid' => 'Sinais de líquido',
        'housing' => 'Carcaça (marcas de queda)',
    ];

    public const CHECKLIST_STATES = [
        'ok' => 'OK',
        'defective' => 'Defeito',
        'untested' => 'Não testado',
    ];

    // number, status, tenant_id, created_by e entry_checklist ficam FORA:
    // quem define é o ServiceOrderService.
    protected $fillable = [
        'customer_id',
        'device_id',
        'technician_id',
        'reported_issue',
        'accessories',
        'device_password',
        'due_date',
        'internal_notes',
    ];

    // A senha do aparelho nunca sai em toArray()/JSON.
    protected $hidden = ['device_password'];

    protected function casts(): array
    {
        return [
            'status' => ServiceOrderStatus::class,
            'entry_checklist' => 'array',
            'device_password' => 'encrypted',
            'due_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ServiceOrderStatusHistory::class)->orderBy('id');
    }

    /** "0007" */
    public function displayNumber(): string
    {
        return str_pad((string) $this->number, 4, '0', STR_PAD_LEFT);
    }
}