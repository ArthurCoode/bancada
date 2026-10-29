@extends('layouts.app')

@section('title', $customer->name)

@section('content')
    <div class="max-w-2xl rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-slate-500">Telefone</dt>
                <dd class="font-medium">
                    {{ $customer->formattedPhone() }}
                    <a href="https://wa.me/55{{ $customer->phone }}" target="_blank" rel="noopener"
                       class="ml-2 text-green-700 underline">WhatsApp</a>
                </dd>
            </div>

            <div>
                <dt class="text-slate-500">E-mail</dt>
                <dd class="font-medium">{{ $customer->email ?? '—' }}</dd>
            </div>

            <div class="sm:col-span-2">
                <dt class="text-slate-500">Observações</dt>
                <dd class="whitespace-pre-line">{{ $customer->notes ?? '—' }}</dd>
            </div>
        </dl>

        <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-4 text-sm">
            <a href="{{ route('customers.edit', $customer) }}"
               class="rounded-md bg-slate-900 px-4 py-2 font-medium text-white hover:bg-slate-800">Editar</a>

            @can('delete', $customer)
                <form method="POST" action="{{ route('customers.destroy', $customer) }}"
                      onsubmit="return confirm('Remover este cliente?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="font-medium text-red-600 underline">Remover</button>
                </form>
            @endcan

            <a href="{{ route('customers.index') }}" class="ml-auto text-slate-600 underline">Voltar</a>
        </div>
    </div>

    <p class="mt-4 text-sm text-slate-500">
        Aparelhos e ordens de serviço deste cliente aparecerão aqui nas próximas fases.
    </p>
@endsection