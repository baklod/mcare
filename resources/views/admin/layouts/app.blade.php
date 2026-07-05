<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'MCARE Admin' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    @php
        $navClass = 'flex items-center justify-between rounded-2xl border px-4 py-3 text-sm font-bold transition';
        $navIdle = 'border-transparent text-slate-600 hover:border-purple-100 hover:bg-purple-50 hover:text-purple-700';
        $navActive = 'border-purple-200 bg-purple-50 text-purple-700 shadow-sm';
    @endphp

    <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-80 bg-gradient-to-b from-purple-100 via-purple-50/70 to-slate-50"></div>

    <aside class="fixed inset-y-0 left-0 z-30 hidden w-72 border-r border-purple-100 bg-white/95 px-5 py-6 shadow-xl shadow-slate-200/60 backdrop-blur-xl lg:flex lg:flex-col">
        <a href="{{ route('admin.enrollments.index') }}" class="flex items-center gap-4 px-2">
            <img src="{{ asset('assets/official-logo.png') }}" alt="Mission Care Training Center logo" class="h-14 w-14 rounded-2xl object-contain">
            <span>
                <span class="block text-base font-black tracking-wide text-slate-900">MCARE</span>
                <span class="block text-xs font-bold uppercase text-purple-600">Admin Center</span>
            </span>
        </a>

        <nav class="mt-9 space-y-8">
            <div>
                <p class="px-4 text-xs font-black uppercase tracking-wide text-slate-400">Main Menu</p>
                <div class="mt-3 space-y-2">
                    <a href="{{ route('admin.enrollments.index') }}" class="{{ $navClass }} {{ request()->routeIs('admin.enrollments.*', 'admin.dashboard') ? $navActive : $navIdle }}">
                        <span>Enrollment Queue</span>
                        <span class="text-xs">Review</span>
                    </a>
                    <a href="{{ route('admin.payment-schedules.index') }}" class="{{ $navClass }} {{ request()->routeIs('admin.payment-schedules.*') ? $navActive : $navIdle }}">
                        <span>Payment Scheduling</span>
                        <span class="text-xs">Queue</span>
                    </a>
                </div>
            </div>

            <details class="group" @if(request()->routeIs('admin.schedules.*', 'admin.payment-schedules.*')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-2xl px-4 py-3 text-sm font-black text-slate-700 hover:bg-purple-50 hover:text-purple-700">
                    <span>Scheduling</span>
                    <span class="text-xs transition group-open:rotate-180">v</span>
                </summary>
                <div class="mt-2 space-y-2 pl-3">
                    <a href="{{ route('admin.schedules.index') }}" class="{{ $navClass }} {{ request()->routeIs('admin.schedules.*') ? $navActive : $navIdle }}">
                        <span>Batch Scheduling</span>
                        <span class="text-xs">AM / PM</span>
                    </a>
                    <a href="{{ route('admin.payment-schedules.index') }}" class="{{ $navClass }} {{ request()->routeIs('admin.payment-schedules.*') ? $navActive : $navIdle }}">
                        <span>Payment Scheduling</span>
                        <span class="text-xs">Online / on-site</span>
                    </a>
                </div>
            </details>

            <details class="group" @if(request()->routeIs('admin.logs.*')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-2xl px-4 py-3 text-sm font-black text-slate-700 hover:bg-purple-50 hover:text-purple-700">
                    <span>Admin / IT</span>
                    <span class="text-xs transition group-open:rotate-180">v</span>
                </summary>
                <div class="mt-2 space-y-2 pl-3">
                    <a href="{{ route('admin.logs.index') }}" class="{{ $navClass }} {{ request()->routeIs('admin.logs.*') ? $navActive : $navIdle }}">
                        <span>Admin Logs</span>
                        <span class="text-xs">Security</span>
                    </a>
                    <a href="{{ route('landing') }}" class="{{ $navClass }} {{ $navIdle }}">
                        <span>Public Landing</span>
                        <span class="text-xs">Site</span>
                    </a>
                </div>
            </details>
        </nav>
    </aside>

    <div class="lg:pl-72">
        <header class="sticky top-0 z-20 border-b border-purple-100 bg-white/90 backdrop-blur-xl">
            <div class="flex min-h-20 flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between lg:px-8">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-purple-600">Mission Care Training Center</p>
                    <h1 class="mt-1 text-xl font-black text-slate-900">{{ $title ?? 'MCARE Admin' }}</h1>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.enrollments.index') }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:border-purple-200 hover:text-purple-700 lg:hidden">Queue</a>
                    <a href="{{ route('admin.payment-schedules.index') }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:border-purple-200 hover:text-purple-700 lg:hidden">Payments</a>
                    <a href="{{ route('admin.schedules.index') }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:border-purple-200 hover:text-purple-700 lg:hidden">Batch</a>

                    <details class="relative">
                        <summary class="flex cursor-pointer list-none items-center gap-3 rounded-full border border-slate-200 bg-white py-2 pl-3 pr-4 text-sm font-bold text-slate-700 shadow-sm hover:border-purple-200 hover:text-purple-700">
                            <span class="grid h-9 w-9 place-items-center rounded-full bg-purple-50 text-purple-700">{{ strtoupper(substr(auth()->user()?->name ?? 'A', 0, 1)) }}</span>
                            <span>{{ auth()->user()?->name ?? 'Admin' }}</span>
                            <span class="text-xs">v</span>
                        </summary>
                        <div class="absolute right-0 mt-3 w-64 overflow-hidden rounded-3xl border border-slate-100 bg-white p-2 shadow-2xl shadow-slate-200">
                            <a href="{{ route('admin.logs.index') }}" class="block rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Admin logs</a>
                            <a href="{{ route('admin.schedules.index') }}" class="block rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Batch scheduling</a>
                            <a href="{{ route('admin.payment-schedules.index') }}" class="block rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Payment scheduling</a>
                            @auth
                                <form method="POST" action="{{ route('admin.logout') }}" class="mt-2 border-t border-slate-100 pt-2">
                                    @csrf
                                    <button type="submit" class="w-full rounded-2xl px-4 py-3 text-left text-sm font-bold text-red-600 hover:bg-red-50">Sign out</button>
                                </form>
                            @endauth
                        </div>
                    </details>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-5 py-8 lg:px-8 lg:py-10">
            @if (session('saved'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold leading-6 text-emerald-700">
                    {{ session('saved') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
