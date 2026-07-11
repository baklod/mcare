<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'MCARE Trainee' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dashboard-shell">
    @php
        $navItem = 'dashboard-nav-link';
    @endphp

    <div class="dashboard-gradient"></div>
    <div class="dashboard-backdrop" data-dashboard-backdrop></div>

    <aside class="dashboard-sidebar" data-dashboard-sidebar>
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('trainee.dashboard') }}" class="dashboard-brand">
                <img src="{{ asset('assets/official-logo.png') }}" alt="Mission Care Training Center logo" class="dashboard-brand-logo">
                <span>
                    <span class="dashboard-brand-title">MCARE</span>
                    <span class="dashboard-brand-subtitle">Trainee Portal</span>
                </span>
            </a>
            <button type="button" class="rounded-xl bg-white/10 px-3 py-2 text-sm font-black text-white lg:hidden" data-dashboard-menu-close>Close</button>
        </div>

        <nav class="mt-6 flex-1 overflow-y-auto pb-24 lg:pb-0">
            <p class="dashboard-menu-label">Trainee Menu</p>
            <div class="space-y-2">
                <a href="#dashboard" class="{{ $navItem }} is-active"><span>Dashboard</span><span class="text-xs text-white/45">Home</span></a>
                <a href="#modules" class="{{ $navItem }}"><span>My Modules</span><span class="text-xs text-white/45">LMS</span></a>
                <a href="#schedule" class="{{ $navItem }}"><span>Schedule</span><span class="text-xs text-white/45">Class</span></a>
                <a href="#payments" class="{{ $navItem }}"><span>Payments</span><span class="text-xs text-white/45">Status</span></a>
                <a href="#documents" class="{{ $navItem }}"><span>Documents</span><span class="text-xs text-white/45">TESDA</span></a>
            </div>

            <form method="POST" action="{{ route('trainee.logout') }}" class="mt-auto">
                @csrf
                <button type="submit" class="mt-8 w-full rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-left text-sm font-bold text-white/80 hover:bg-white/15 hover:text-white">
                    Sign out
                </button>
            </form>
        </nav>
    </aside>

    <div class="lg:pl-72">
        <header class="dashboard-topbar">
            <div class="flex min-h-20 flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between lg:px-8">
                <div class="flex items-center gap-3">
                    <button type="button" class="rounded-2xl border border-purple-100 bg-white px-4 py-3 text-sm font-black text-purple-700 shadow-sm lg:hidden" data-dashboard-menu-open>Menu</button>
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-purple-600">Caregiving NC II Program</p>
                        <h1 class="mt-1 font-display text-xl font-black text-slate-900">{{ $title ?? 'Trainee Portal' }}</h1>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="#modules" class="secondary-action py-2.5 lg:hidden">Modules</a>
                    <div class="flex items-center gap-3 rounded-full border border-slate-200 bg-white py-2 pl-3 pr-4 text-sm font-bold text-slate-700 shadow-sm">
                        <span class="grid h-9 w-9 place-items-center rounded-full bg-purple-50 text-purple-700">{{ strtoupper(substr(auth()->user()?->name ?? 'T', 0, 1)) }}</span>
                        <span class="hidden text-left sm:block">
                            <span class="block">{{ auth()->user()?->name ?? 'Trainee' }}</span>
                            <span class="block text-xs font-semibold text-slate-400">{{ \App\Support\AccountPortal::roleLabelFor(auth()->user()) }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </header>

        <main class="dashboard-main pb-28 lg:pb-9">
            @if (session('saved'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold leading-6 text-emerald-700">
                    {{ session('saved') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <nav class="dashboard-mobile-bar">
        <a href="#dashboard" class="dashboard-mobile-link">Home</a>
        <a href="#modules" class="dashboard-mobile-link">Modules</a>
        <a href="#schedule" class="dashboard-mobile-link">Sched</a>
        <a href="#payments" class="dashboard-mobile-link">Pay</a>
    </nav>
</body>
</html>
