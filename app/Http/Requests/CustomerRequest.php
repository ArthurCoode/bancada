<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => $this->normalizePhone($this->input('phone')),
            'email' => $this->filled('email') ? Str::lower($this->input('email')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'regex:/^\d{10,11}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Informe um telefone válido, com DDD (10 ou 11 dígitos).',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'phone' => 'telefone',
            'email' => 'e-mail',
            'notes' => 'observações',
        ];
    }

    /** Fica só com os dígitos e remove o código do país (55), se vier junto. */
    private function normalizePhone(mixed $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (in_array(strlen($digits), [12, 13], true) && str_starts_with($digits, '55')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }
}