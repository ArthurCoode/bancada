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

    {{-- Aparelhos do cliente --}}
    <div class="mt-6 max-w-2xl">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-base font-semibold">Aparelhos</h2>

            <a href="{{ route('devices.create', ['customer' => $customer->id]) }}"
               class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Adicionar aparelho
            </a>
        </div>

        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-left text-sm">
                <tbody class="divide-y divide-slate-100">
                    @forelse ($devices as $device)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $device->label() }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $device->serial_number ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $device->color ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ route('orders.create', ['customer' => $customer->id, 'device' => $device->id]) }}"
                                   class="font-medium text-green-700 underline">Abrir OS</a>
                                <a href="{{ route('devices.edit', $device) }}"
                                   class="ml-3 font-medium text-slate-700 underline">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-6 text-center text-slate-500">Nenhum aparelho cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="mt-4 text-sm text-slate-500">
            O histórico de ordens de serviço deste cliente aparece aqui na próxima etapa.
        </p>
    </div>
@endsection