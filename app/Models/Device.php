<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    use BelongsToTenant, HasFactory;

    // customer_id é liberado, mas SEMPRE validado pelo DeviceRequest (dentro da loja).
    protected $fillable = ['customer_id', 'brand', 'model', 'serial_number', 'color', 'notes'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** "Samsung Galaxy A54" */
    public function label(): string
    {
        return trim($this->brand.' '.$this->model);
    }
}