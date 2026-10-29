<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('serial_number')) {
            $this->merge(['serial_number' => Str::upper($this->input('serial_number'))]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'brand' => ['required', 'string', 'max:60'],
            'model' => ['required', 'string', 'max:80'],
            'serial_number' => ['nullable', 'string', 'max:40', 'regex:/^[A-Z0-9\-\/]+$/'],
            'color' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        // Só no cadastro. Na edição o dono do aparelho não muda.
        if ($this->isMethod('POST')) {
            $rules['customer_id'] = [
                'required',
                'integer',
                // Consulta direta ao banco NÃO passa pelo TenantScope: filtramos a loja na mão.
                Rule::exists('customers', 'id')->where('tenant_id', $this->user()->tenant_id),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'customer_id.exists' => 'Selecione um cliente válido.',
            'serial_number.regex' => 'Use apenas letras, números, hífen ou barra.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'cliente',
            'brand' => 'marca',
            'model' => 'modelo',
            'serial_number' => 'IMEI/número de série',
            'color' => 'cor',
            'notes' => 'observações',
        ];
    }
}