@extends('layouts.app')

@section('title', 'Equipe')

@section('content')
    <div class="mb-4 flex items-center justify-between gap-4">
        <p class="text-sm text-slate-600">Pessoas com acesso a {{ auth()->user()->tenant->name }}.</p>

        <a href="{{ route('team.create') }}"
           class="shrink-0 rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Adicionar membro
        </a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Nome</th>
                    <th class="px-4 py-3">E-mail</th>
                    <th class="px-4 py-3">Papel</th>
                    <th class="px-4 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($members as $member)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $member->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $member->email }}</td>
                        <td class="px-4 py-3">{{ $member->role->label() }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('update', $member)
                                <a href="{{ route('team.edit', $member->id) }}"
                                   class="font-medium text-slate-700 underline">Editar</a>

                                <form method="POST" action="{{ route('team.destroy', $member->id) }}"
                                      class="ml-3 inline"
                                      onsubmit="return confirm('Remover este membro da equipe?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-red-600 underline">Remover</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection