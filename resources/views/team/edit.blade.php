@extends('layouts.app')

@section('title', 'Editar membro')

@section('content')
    <div class="max-w-lg rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <p class="mb-4 text-sm text-slate-600">E-mail: <span class="font-medium">{{ $member->email }}</span></p>

        <form method="POST" action="{{ route('team.update', $member->id) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <x-field name="name" label="Nome" :value="$member->name" required autofocus />
            <x-select name="role" label="Papel" :options="$roles" :selected="$member->role->value" />
            <x-field name="password" label="Nova senha (deixe em branco para manter)" type="password" autocomplete="new-password" />
            <x-field name="password_confirmation" label="Confirmar nova senha" type="password" autocomplete="new-password" />

            <div class="flex items-center gap-3">
                <button type="submit"
                        class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    Salvar
                </button>
                <a href="{{ route('team.index') }}" class="text-sm text-slate-600 underline">Cancelar</a>
            </div>
        </form>
    </div>
@endsection