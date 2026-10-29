@extends('layouts.app')

@section('title', 'OS '.$order->displayNumber())

@php
    $stateClasses = [
        'ok' => 'bg-green-100 text-green-800',
        'defective' => 'bg-red-100 text-red-800',
        'untested' => 'bg-slate-100 text-slate-600',
    ];
@endphp

@section('content')
    <div class="grid max-w-5xl grid-cols-1 gap-6 lg:grid-cols-3">

        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">OS {{ $order->displayNumber() }}</h2>
                    <x-status-badge :status="$order->status" />
                </div>

                <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-500">Cliente</dt>
                        <dd class="font-medium">
                            <a href="{{ route('customers.show', $order->customer_id) }}" class="underline">{{ $order->customer->name }}</a>
                            <span class="block text-slate-600">{{ $order->customer->formattedPhone() }}</span>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-slate-500">Aparelho</dt>
                        <dd class="font-medium">
                            {{ $order->device->label() }}
                            <span class="block text-slate-600">
                                {{ $order->device->serial_number ?? 'Sem IMEI/série' }}
                                @if ($order->device->color) · {{ $order->device->color }} @endif
                            </span>
                        </dd>
                    </div>

                    <div class="sm:col-span-2">
                        <dt class="text-slate-500">Defeito relatado</dt>
                        <dd class="whitespace-pre-line">{{ $order->reported_issue }}</dd>
                    </div>

                    <div>
                        <dt class="text-slate-500">Acessórios deixados</dt>
                        <dd>{{ $order->accessories ?? '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-slate-500">Senha / desbloqueio</dt>
                        <dd>
                            @can('viewDevicePassword', $order)
                                {{ $order->device_password ?? '—' }}
                            @else
                                <span class="text-slate-400">Restrito ao técnico e ao dono</span>
                            @endcan
                        </dd>
                    </div>

                    <div>
                        <dt class="text-slate-500">Prazo previsto</dt>
                        <dd>{{ $order->due_date?->format('d/m/Y') ?? '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-slate-500">Técnico</dt>
                        <dd>{{ $order->technician?->name ?? '—' }}</dd>
                    </div>

                    @if ($order->internal_notes)
                        <div class="sm:col-span-2">
                            <dt class="text-slate-500">Observações internas</dt>
                            <dd class="whitespace-pre-line">{{ $order->internal_notes }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-3 text-base font-semibold">Checklist de entrada</h3>

                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach ($checklistItems as $key => $label)
                        @php $state = $order->entry_checklist[$key] ?? 'untested'; @endphp
                        <li class="flex items-center justify-between py-2">
                            <span>{{ $label }}</span>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $stateClasses[$state] }}">
                                {{ $checklistStates[$state] }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Histórico --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm lg:self-start">
            <h3 class="mb-3 text-base font-semibold">Histórico</h3>

            <ol class="space-y-4 text-sm">
                @foreach ($order->statusHistory as $entry)
                    <li>
                        <x-status-badge :status="$entry->to_status" />
                        <p class="mt-1 text-slate-600">
                            {{ $entry->created_at->format('d/m/Y H:i') }}
                            @if ($entry->user) · {{ $entry->user->name }} @endif
                        </p>
                        @if ($entry->note)
                            <p class="text-slate-500">{{ $entry->note }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    </div>

    <p class="mt-6 text-sm">
        <a href="{{ route('orders.index') }}" class="text-slate-600 underline">Voltar para as ordens de serviço</a>
    </p>
@endsection