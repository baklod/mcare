<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#faf9f7]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'MCARE Alumni' }}</title>
    <link rel="preload" as="image" href="{{ asset('assets/mcare-mark.png') }}" fetchpriority="high">
    <x-dashboard-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dashboard-shell universal-dashboard" data-dashboard-role="alumni">
    <div class="dashboard-navigation-progress" aria-hidden="true"></div>
    @php
        $alumniName = auth()->user()?->name ?? 'Alumni';
        $alumniInitial = strtoupper(substr($alumniName, 0, 1));
        $alumniNav = [
            ['label' => 'Career Hub', 'icon' => 'fa-briefcase', 'href' => route('alumni.dashboard'), 'active' => request()->routeIs('alumni.dashboard')],
            ['label' => 'Notifications', 'icon' => 'fa-bell', 'href' => route('notifications.index'), 'active' => request()->routeIs('notifications.*')],
        ];
    @endphp

    <div class="dashboard-backdrop" data-dashboard-backdrop></div>

    <aside class="dashboard-sidebar" data-dashboard-sidebar>
        <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-3">
            <a href="{{ route('alumni.dashboard') }}" class="dashboard-brand flex-1 min-w-0">
                <img src="{{ asset('assets/mcare-mark.png') }}" alt="MCARE mark" class="dashboard-brand-logo" width="44" height="44" loading="eager" decoding="sync" fetchpriority="high">
                <span class="min-w-0"><span class="dashboard-brand-title">MCARE Hub</span><span class="dashboard-brand-subtitle">Alumni Portal</span></span>
            </a>
            <button type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-purple-300 hover:bg-purple-50 hover:text-purple-700 focus-visible:ring-2 focus-visible:ring-purple-500" data-dashboard-sidebar-collapse data-dashboard-menu-close aria-label="Collapse navigation" title="Collapse navigation">
                <x-dashboard-icon name="chevron-left" class="h-4 w-4" />
            </button>
        </div>

        <nav class="dashboard-nav" aria-label="Alumni navigation">
            <p class="dashboard-menu-label">Alumni services</p>
            @foreach ($alumniNav as $item)
                <a href="{{ $item['href'] }}" data-dashboard-prefetch class="dashboard-nav-link {{ $item['active'] ? 'is-active' : '' }}" @if($item['active']) aria-current="page" @endif>
                    <x-dashboard-icon :name="$item['icon']" class="dashboard-nav-icon" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
            <a href="{{ route('landing') }}" class="dashboard-nav-link"><x-dashboard-icon name="arrow-up-right-from-square" class="dashboard-nav-icon" /><span>Public site</span></a>
        </nav>

        <details class="dashboard-sidebar-footer" data-dashboard-account>
            <summary class="dashboard-account-summary">
                <span class="dashboard-account-avatar">{{ $alumniInitial }}</span>
                <span class="min-w-0 flex-1"><span class="block truncate text-sm font-bold text-slate-950">{{ $alumniName }}</span><span class="block text-xs text-slate-500">Alumni</span></span>
                <x-dashboard-icon name="chevron-down" class="dashboard-chevron text-xs text-slate-400 transition" />
            </summary>
            <div class="dashboard-account-menu"><x-dashboard-account-actions :logout-route="route('logout')" role-label="Alumni" /></div>
        </details>
    </aside>

    <div class="dashboard-layout">
        <header class="dashboard-topbar">
            <div class="dashboard-topbar-inner">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" class="dashboard-menu-button" data-dashboard-menu-open aria-label="Open navigation"><x-dashboard-icon name="bars" /><span class="hidden sm:inline">Menu</span></button>
                    <div class="min-w-0"><p class="dashboard-header-kicker">Mission Care Training Center</p><h1 class="dashboard-header-title">{{ $title ?? 'MCARE Alumni' }}</h1></div>
                </div>
                <div class="flex items-center gap-2">
                    <details class="relative shrink-0 justify-self-end" data-dashboard-account>
                        <summary class="dashboard-account-summary">
                            <span class="dashboard-account-avatar h-9 w-9">{{ $alumniInitial }}</span>
                            <span class="hidden text-left sm:block"><span class="block text-sm font-bold">{{ $alumniName }}</span><span class="block text-xs font-semibold text-slate-400">Alumni</span></span>
                            <x-dashboard-icon name="chevron-down" class="dashboard-chevron text-xs text-slate-400 transition" />
                        </summary>
                        <div class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-xl"><x-dashboard-account-actions :logout-route="route('logout')" role-label="Alumni" /></div>
                    </details>
                </div>
            </div>
        </header>

        <main class="dashboard-main pb-28 lg:pb-9">
            @if (session('saved'))<div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold leading-6 text-emerald-700">{{ session('saved') }}</div>@endif
            @yield('content')
        </main>
    </div>

    <nav class="dashboard-mobile-bar grid-cols-2" aria-label="Mobile alumni navigation">
        @foreach ($alumniNav as $item)
            <a href="{{ $item['href'] }}" data-dashboard-prefetch class="dashboard-mobile-link {{ $item['active'] ? 'is-active' : '' }}" @if($item['active']) aria-current="page" @endif><x-dashboard-icon :name="$item['icon']" /><span class="truncate">{{ $item['label'] }}</span></a>
        @endforeach
    </nav>
</body>
</html>
