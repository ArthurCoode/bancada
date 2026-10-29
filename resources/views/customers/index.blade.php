@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('customers.index') }}" class="flex gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nome ou telefone"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none sm:w-72">
            <button type="submit"
                    class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                Buscar
            </button>
        </form>

        <a href="{{ route('customers.create') }}"
           class="shrink-0 rounded-md bg-slate-900 px-4 py-2 text-center text-sm font-medium text-white hover:bg-slate-800">
            Novo cliente
        </a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Nome</th>
                    <th class="px-4 py-3">Telefone</th>
                    <th class="px-4 py-3">E-mail</th>
                    <th class="px-4 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($customers as $customer)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('customers.show', $customer) }}"
                               class="font-medium text-slate-900 hover:underline">{{ $customer->name }}</a>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $customer->formattedPhone() }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $customer->email ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('customers.edit', $customer) }}"
                               class="font-medium text-slate-700 underline">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                            Nenhum cliente encontrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $customers->links() }}
    </div>
@endsection