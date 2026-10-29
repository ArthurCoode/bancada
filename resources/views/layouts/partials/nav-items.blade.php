@php
    $mobile = $mobile ?? false;
    $base = $mobile
        ? 'whitespace-nowrap rounded-full px-3 py-1.5'
        : 'flex items-center rounded-md px-3 py-2';

    $items = [
        ['label' => 'Início',            'route' => 'home',       'active' => 'home',   'enabled' => true],
        ['label' => 'Clientes',          'route' => null,         'enabled' => false],
        ['label' => 'Aparelhos',         'route' => null,         'enabled' => false],
        ['label' => 'Ordens de serviço', 'route' => null,         'enabled' => false],
        ['label' => 'Equipe',            'route' => 'team.index', 'active' => 'team.*', 'enabled' => true, 'owner_only' => true],
    ];

    // Esconde os itens exclusivos do dono para os demais papéis.
    $items = array_filter($items, fn ($item) => ! ($item['owner_only'] ?? false) || auth()->user()->isOwner());
@endphp

@foreach ($items as $item)
    @if ($item['enabled'])
        @php $active = request()->routeIs($item['active']); @endphp
        <a href="{{ route($item['route']) }}"
           class="{{ $base }} {{ $active ? 'bg-slate-700 text-white' : ($mobile ? 'bg-slate-100 text-slate-700' : 'text-slate-300 hover:bg-slate-800') }}">
            {{ $item['label'] }}
        </a>
    @else
        <span class="{{ $base }} cursor-not-allowed {{ $mobile ? 'bg-slate-50 text-slate-400' : 'text-slate-600' }}"
              title="Disponível em uma fase futura">
            {{ $item['label'] }}
        </span>
    @endif
@endforeach