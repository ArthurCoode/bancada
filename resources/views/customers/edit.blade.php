@extends('layouts.app')

@section('title', 'Editar cliente')

@section('content')
    <div class="max-w-lg rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('customers.update', $customer) }}" class="space-y-4">
            @csrf
            @method('PUT')

            @include('customers._form', [
                'customer' => $customer,
                'submitLabel' => 'Salvar',
                'cancelUrl' => route('customers.show', $customer),
            ])
        </form>
    </div>
@endsection