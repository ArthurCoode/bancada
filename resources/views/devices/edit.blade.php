@extends('layouts.app')

@section('title', 'Editar aparelho')

@section('content')
    <div class="max-w-lg rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <p class="mb-4 text-sm text-slate-600">
            Cliente:
            <a href="{{ route('customers.show', $device->customer_id) }}"
               class="font-medium text-slate-900 underline">{{ $device->customer->name }}</a>
        </p>

        <form method="POST" action="{{ route('devices.update', $device) }}" class="space-y-4">
            @csrf
            @method('PUT')

            @include('devices._form', [
                'device' => $device,
                'submitLabel' => 'Salvar',
                'cancelUrl' => route('customers.show', $device->customer_id),
            ])
        </form>

        @can('delete', $device)
            <form method="POST" action="{{ route('devices.destroy', $device) }}"
                  class="mt-6 border-t border-slate-100 pt-4"
                  onsubmit="return confirm('Remover este aparelho?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm font-medium text-red-600 underline">Remover aparelho</button>
            </form>
        @endcan
    </div>
@endsection