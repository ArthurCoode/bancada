<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpenServiceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            // Cliente: precisa ser DESTA loja.
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where('tenant_id', $tenantId),
            ],

            // Aparelho: desta loja E do cliente informado.
            'device_id' => [
                'required',
                'integer',
                Rule::exists('devices', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('customer_id', (int) $this->input('customer_id')),
            ],

            // Técnico: desta loja E com papel de dono ou técnico.
            'technician_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereIn('role', [Role::Owner->value, Role::Technician->value]),
            ],

            'reported_issue' => ['required', 'string', 'max:2000'],
            'accessories' => ['nullable', 'string', 'max:500'],
            'device_password' => ['nullable', 'string', 'max:100'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],

            'checklist' => ['nullable', 'array'],
            'checklist.*' => ['in:ok,defective,untested'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.exists' => 'Selecione um cliente válido.',
            'device_id.exists' => 'Selecione um aparelho válido deste cliente.',
            'technician_id.exists' => 'Selecione um técnico válido.',
            'due_date.after_or_equal' => 'O prazo não pode ser anterior a hoje.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'cliente',
            'device_id' => 'aparelho',
            'technician_id' => 'técnico',
            'reported_issue' => 'defeito relatado',
            'accessories' => 'acessórios',
            'device_password' => 'senha do aparelho',
            'due_date' => 'prazo',
            'internal_notes' => 'observações internas',
        ];
    }
}