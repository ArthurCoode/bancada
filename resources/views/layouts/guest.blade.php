<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10 text-slate-800 antialiased">
    <div class="w-full max-w-md">
        <div class="mb-6 text-center text-2xl font-semibold tracking-tight">
            {{ config('app.name') }}
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            @yield('content')
        </div>
    </div>
</body>
</html>