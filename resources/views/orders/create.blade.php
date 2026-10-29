@extends('layouts.app')

@section('title', 'Abrir OS')

@section('content')
    <div class="max-w-2xl rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <p class="mb-4 text-sm text-slate-600">
            Cliente: <span class="font-medium text-slate-900">{{ $customer->name }}</span>
            · {{ $customer->formattedPhone() }}
        </p>

        @if (count($deviceOptions) === 0)
            <p class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Este cliente ainda não tem aparelhos cadastrados.
                <a href="{{ route('devices.create', ['customer' => $customer->id]) }}"
                   class="font-medium underline">Cadastrar aparelho</a>
            </p>
        @else
            <form method="POST" action="{{ route('orders.store') }}" class="space-y-5">
                @csrf

                {{-- Campo escondido, mas controlável pelo usuário: por isso é validado dentro da loja. --}}
                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                @error('customer_id')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror

                <x-select name="device_id" label="Aparelho" :options="$deviceOptions" :selected="$selectedDevice"
                          placeholder="Selecione o aparelho" required />

                <div>
                    <label for="reported_issue" class="mb-1 block text-sm font-medium text-slate-700">Defeito relatado pelo cliente</label>
                    <textarea id="reported_issue" name="reported_issue" rows="3" required
                              class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">{{ old('reported_issue') }}</textarea>
                    @error('reported_issue')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <x-field name="accessories" label="Acessórios deixados (opcional)" placeholder="Ex.: capinha, carregador, chip" />

                {{-- Checklist de entrada --}}
                <fieldset>
                    <legend class="mb-1 text-sm font-medium text-slate-700">Checklist de entrada</legend>
                    <div class="divide-y divide-slate-100 rounded-md border border-slate-200">
                        @foreach ($checklistItems as $key => $label)
                            <div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 text-sm">
                                <span>{{ $label }}</span>
                                <div class="flex gap-4">
                                    @foreach ($checklistStates as $value => $text)
                                        <label class="flex items-center gap-1">
                                            <input type="radio" name="checklist[{{ $key }}]" value="{{ $value }}"
                                                   @checked(old("checklist.$key", 'untested') === $value)>
                                            {{ $text }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('checklist.*')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </fieldset>

                <x-field name="device_password" label="Senha ou padrão de desbloqueio (opcional)" autocomplete="off" />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-field name="due_date" label="Prazo previsto (opcional)" type="date" />
                    <x-select name="technician_id" label="Técnico responsável (opcional)"
                              :options="$technicianOptions" placeholder="Sem técnico definido" />
                </div>

                <div>
                    <label for="internal_notes" class="mb-1 block text-sm font-medium text-slate-700">Observações internas (opcional)</label>
                    <textarea id="internal_notes" name="internal_notes" rows="2"
                              class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">{{ old('internal_notes') }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">Visíveis só para a equipe. Nunca aparecem para o cliente.</p>
                    @error('internal_notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                            class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                        Abrir OS
                    </button>
                    <a href="{{ route('customers.show', $customer) }}" class="text-sm text-slate-600 underline">Cancelar</a>
                </div>
            </form>
        @endif
    </div>
@endsection