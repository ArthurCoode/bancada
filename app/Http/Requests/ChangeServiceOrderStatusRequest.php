<?php

namespace App\Http\Requests;

use App\Enums\ServiceOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeServiceOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $to = $this->enum('status', ServiceOrderStatus::class);

        // Status inexistente: deixa a validação responder com o erro de campo.
        if ($to === null) {
            return true;
        }

        // A OS já veio do route binding, ou seja, já é da loja do usuário (outra loja = 404).
        return $this->user()->can('changeStatus', [$this->route('order'), $to]);
    }

    public function rules(): array
    {
        $to = $this->enum('status', ServiceOrderStatus::class);

        return [
            'status' => ['required', Rule::enum(ServiceOrderStatus::class)],
            'note' => [
                Rule::requiredIf($to?->requiresNote() ?? false),
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum' => 'Selecione um status válido.',
            'note.required' => 'Informe o motivo ao reprovar ou cancelar.',
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'status',
            'note' => 'observação',
        ];
    }
}