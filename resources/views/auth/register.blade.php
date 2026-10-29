@extends('layouts.guest')

@section('title', 'Criar conta')

@section('content')
    <h1 class="mb-4 text-lg font-semibold">Criar conta</h1>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <x-field name="shop_name" label="Nome da loja" required autofocus />
        <x-field name="name" label="Seu nome" autocomplete="name" required />
        <x-field name="email" label="E-mail" type="email" autocomplete="username" required />
        <x-field name="password" label="Senha (mínimo 8 caracteres)" type="password" autocomplete="new-password" required />
        <x-field name="password_confirmation" label="Confirmar senha" type="password" autocomplete="new-password" required />

        <button type="submit"
                class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Criar conta
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-slate-600">
        Já tem conta?
        <a href="{{ route('login') }}" class="font-medium text-slate-900 underline">Entrar</a>
    </p>
@endsection