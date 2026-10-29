@extends('layouts.app')

@section('title', 'Início')

@section('content')
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold">Bem-vindo ao {{ config('app.name') }}</h2>
        <p class="mt-2 text-slate-600">
            Sistema de ordens de serviço para assistência técnica.
            A base do projeto está pronta; as telas entram fase a fase.
        </p>
    </div>
@endsection