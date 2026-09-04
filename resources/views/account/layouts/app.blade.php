<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Account | MCARE' }}</title>
    <x-dashboard-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="account-page min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="account-header border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
            <a href="{{ $portalUrl }}" class="flex min-w-0 items-center gap-3">
                <img src="{{ asset('assets/images/logoicon.png') }}" alt="MCARE Hub" class="h-11 w-11 rounded-xl border border-slate-100 object-contain p-1">
                <span>
                    <strong class="block text-sm sm:text-base">MCARE Hub</strong>
                    <span class="text-xs font-semibold text-purple-700">{{ $roleLabel }} account</span>
                </span>
            </a>
            <a href="{{ $portalUrl }}" class="secondary-action">
                <x-dashboard-icon name="arrow-left" class="h-4 w-4" />
                <span>Back</span>
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-5 py-8 sm:px-8 sm:py-12">
        @if (session('saved'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800" role="status" aria-live="polite" data-auto-dismiss="5000">{{ session('saved') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-800" role="alert">
                <p>{{ $errors->first() }}</p>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
