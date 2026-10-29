@extends('layouts.guest')

@section('title', 'Entrar')

@section('content')
    <h1 class="mb-4 text-lg font-semibold">Entrar</h1>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <x-field name="email" label="E-mail" type="email" autocomplete="username" required autofocus />
        <x-field name="password" label="Senha" type="password" autocomplete="current-password" required />

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" value="1" class="rounded border-slate-300">
            Manter conectado
        </label>

        <button type="submit"
                class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Entrar
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-slate-600">
        Ainda não tem conta?
        <a href="{{ route('register') }}" class="font-medium text-slate-900 underline">Criar conta</a>
    </p>
@endsection