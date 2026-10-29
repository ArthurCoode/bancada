<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Início') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <div class="flex min-h-screen">

        {{-- Sidebar (a partir de telas médias) --}}
        <aside class="hidden w-60 shrink-0 flex-col bg-slate-900 text-slate-200 md:flex">
            <div class="px-6 py-5 text-lg font-semibold tracking-tight text-white">
                {{ config('app.name') }}
            </div>

            <nav class="flex-1 space-y-1 px-3 py-2 text-sm">
                @include('layouts.partials.nav-items')
            </nav>

            <div class="px-6 py-4 text-xs text-slate-500">
                Projeto de estudo
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">

            {{-- Topbar --}}
            <header class="border-b border-slate-200 bg-white">
                <div class="flex items-center justify-between px-4 py-3 md:px-8">
                    <h1 class="text-base font-semibold md:text-lg">@yield('title', 'Início')</h1>
                    <span class="text-sm text-slate-500">{{ now()->translatedFormat('d \d\e F \d\e Y') }}</span>
                </div>

                {{-- Navegação para celular --}}
                <nav class="flex gap-2 overflow-x-auto border-t border-slate-100 px-4 py-2 text-sm md:hidden">
                    @include('layouts.partials.nav-items', ['mobile' => true])
                </nav>
            </header>

            {{-- Conteúdo --}}
            <main class="flex-1 px-4 py-6 md:px-8">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>