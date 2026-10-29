@extends('layouts.app')

@section('title', 'Novo aparelho')

@section('content')
    <div class="max-w-lg rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <p class="mb-4 text-sm text-slate-600">
            Cliente: <span class="font-medium text-slate-900">{{ $customer->name }}</span>
        </p>

        <form method="POST" action="{{ route('devices.store') }}" class="space-y-4">
            @csrf

            {{-- Campo escondido, mas controlável pelo usuário: por isso é validado dentro da loja. --}}
            <input type="hidden" name="customer_id" value="{{ $customer->id }}">
            @error('customer_id')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            @include('devices._form', [
                'device' => null,
                'submitLabel' => 'Cadastrar',
                'cancelUrl' => route('customers.show', $customer),
            ])
        </form>
    </div>
@endsection