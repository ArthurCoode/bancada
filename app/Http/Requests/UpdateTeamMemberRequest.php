<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', Rule::enum(Role::class)->only([Role::Technician, Role::Attendant])],
            // Em branco = manter a senha atual.
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}