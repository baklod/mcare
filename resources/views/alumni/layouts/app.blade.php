<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#faf9f7]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'MCARE Alumni' }}</title>
    <x-dashboard-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dashboard-shell universal-dashboard" data-dashboard-role="alumni">
    <div class="dashboard-navigation-progress" aria-hidden="true"></div>
    @php
        $alumniName = auth()->user()?->name ?? 'Alumni';
        $alumniNav = [
            ['label' => 'Career Hub', 'icon' => 'fa-briefcase', 'href' => route('alumni.dashboard'), 'active' => request()->routeIs('alumni.dashboard')],
            ['label' => 'Notifications', 'icon' => 'fa-bell', 'href' => route('notifications.index'), 'active' => request()->routeIs('notifications.*')],
        ];
        $alumniMobileNav = array_merge($alumniNav, [
            ['label' => 'Public site', 'icon' => 'fa-arrow-up-right-from-square', 'href' => route('landing'), 'active' => false],
        ]);
    @endphp

    <aside class="dashboard-sidebar" data-dashboard-sidebar>
        <div class="flex min-h-11 items-center border-b border-slate-100 pb-3">
            <div class="dashboard-brand flex-1 min-w-0">
                <img src="{{ asset('assets/images/logoicon.png') }}" alt="MCARE Hub" class="dashboard-brand-mark">
                <span class="min-w-0"><span class="dashboard-brand-title">MCARE Hub</span><span class="dashboard-brand-subtitle">Alumni Portal</span></span>
            </div>
        </div>

        <nav class="dashboard-nav" aria-label="Alumni navigation">
            <p class="dashboard-menu-label">Alumni services</p>
            @foreach ($alumniNav as $item)
                <a href="{{ $item['href'] }}" data-dashboard-prefetch data-dashboard-nav-key="alumni-{{ str($item['label'])->slug() }}" class="dashboard-nav-link {{ $item['active'] ? 'is-active' : '' }}" @if($item['active']) aria-current="page" @endif>
                    <x-dashboard-icon :name="$item['icon']" class="dashboard-nav-icon" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
            <a href="{{ route('landing') }}" class="dashboard-nav-link"><x-dashboard-icon name="arrow-up-right-from-square" class="dashboard-nav-icon" /><span>Public site</span></a>
        </nav>

        <details class="dashboard-sidebar-footer" data-dashboard-account>
            <summary class="dashboard-account-summary">
                <x-user-avatar :user="auth()->user()" :name="$alumniName" class="dashboard-account-avatar" />
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
                    <div class="min-w-0"><p class="dashboard-header-kicker">Mission Care Training Center</p><h1 class="dashboard-header-title">{{ $title ?? 'MCARE Alumni' }}</h1></div>
                </div>
                <div class="flex items-center gap-2">
                    <details class="relative shrink-0 justify-self-end" data-dashboard-account>
                        <summary class="dashboard-account-summary">
                            <x-user-avatar :user="auth()->user()" :name="$alumniName" class="dashboard-account-avatar h-9 w-9" />
                            <span class="hidden text-left sm:block"><span class="block text-sm font-bold">{{ $alumniName }}</span><span class="block text-xs font-semibold text-slate-400">Alumni</span></span>
                            <x-dashboard-icon name="chevron-down" class="dashboard-chevron text-xs text-slate-400 transition" />
                        </summary>
                        <div class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-xl"><x-dashboard-account-actions :logout-route="route('logout')" role-label="Alumni" :career-hub-route="route('alumni.dashboard')" /></div>
                    </details>
                </div>
            </div>
        </header>

        <main class="dashboard-main pb-28 lg:pb-9">
            @if (session('saved'))
                @php
                    $savedIcon = session('saved_icon', 'circle-check');
                    $savedIsAvailable = session('saved_tone', 'available') === 'available';
                @endphp
                <div class="mb-6 flex items-center gap-3 rounded-lg border px-4 py-3 text-sm font-semibold leading-6 {{ $savedIsAvailable ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-slate-100 text-slate-700' }}" role="status" aria-live="polite" data-auto-dismiss="5000" data-flash-icon="{{ $savedIcon }}">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $savedIsAvailable ? 'bg-emerald-100 text-emerald-700' : 'bg-white text-slate-600' }}"><x-dashboard-icon :name="$savedIcon" class="h-4 w-4" /></span>
                    <span>{{ session('saved') }}</span>
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    <x-dashboard-mobile-navigation
        :primary-items="$alumniMobileNav"
        label="Mobile alumni navigation"
        role="alumni"
    />
</body>
</html>
