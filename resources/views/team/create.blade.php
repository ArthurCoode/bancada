@extends('layouts.app')

@section('title', 'Adicionar membro')

@section('content')
    <div class="max-w-lg rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('team.store') }}" class="space-y-4">
            @csrf

            <x-field name="name" label="Nome" required autofocus />
            <x-field name="email" label="E-mail" type="email" required />
            <x-select name="role" label="Papel" :options="$roles" />
            <x-field name="password" label="Senha inicial (mínimo 8 caracteres)" type="password" autocomplete="new-password" required />
            <x-field name="password_confirmation" label="Confirmar senha" type="password" autocomplete="new-password" required />

            <div class="flex items-center gap-3">
                <button type="submit"
                        class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    Adicionar
                </button>
                <a href="{{ route('team.index') }}" class="text-sm text-slate-600 underline">Cancelar</a>
            </div>
        </form>
    </div>
@endsection