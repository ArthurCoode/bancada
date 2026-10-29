@extends('layouts.app')

@section('title', 'Novo cliente')

@section('content')
    <div class="max-w-lg rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('customers.store') }}" class="space-y-4">
            @csrf

            @include('customers._form', [
                'customer' => null,
                'submitLabel' => 'Cadastrar',
                'cancelUrl' => route('customers.index'),
            ])
        </form>
    </div>
@endsection