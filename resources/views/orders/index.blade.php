@extends('layouts.app')

@section('title', 'Ordens de serviço')

@section('content')
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('orders.index') }}" class="flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Nº, cliente, aparelho ou IMEI"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none sm:w-64">

            <select name="status"
                    class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                <option value="">Todos os status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected($currentStatus === $status)>{{ $status->label() }}</option>
                @endforeach
            </select>

            <button type="submit"
                    class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                Filtrar
            </button>
        </form>

        <a href="{{ route('customers.index') }}" class="text-sm text-slate-600 underline">
            Para abrir uma OS, escolha o cliente e o aparelho
        </a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">OS</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3">Aparelho</th>
                    <th class="px-4 py-3">Técnico</th>
                    <th class="px-4 py-3">Prazo</th>
                    <th class="px-4 py-3">Entrada</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($orders as $order)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3">
                            <a href="{{ route('orders.show', $order) }}"
                               class="font-semibold text-slate-900 hover:underline">{{ $order->displayNumber() }}</a>
                        </td>
                        <td class="px-4 py-3"><x-status-badge :status="$order->status" /></td>
                        <td class="px-4 py-3">{{ $order->customer->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $order->device->label() }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $order->technician?->name ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $order->due_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $order->created_at->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                            Nenhuma ordem de serviço encontrada.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
@endsection