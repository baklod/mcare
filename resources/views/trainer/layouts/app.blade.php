<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#faf9f7]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'MCARE Trainer' }}</title>
    <link rel="preload" as="image" href="{{ asset('assets/mcare-mark.png') }}" fetchpriority="high">
    <x-dashboard-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dashboard-shell universal-dashboard" data-dashboard-role="trainer">
    <div class="dashboard-navigation-progress" aria-hidden="true"></div>
    @php
        $trainerName = auth()->user()?->name ?? 'Trainer User';
        $trainerInitial = strtoupper(substr($trainerName, 0, 1));
        $navClass = 'dashboard-nav-link';
        $navIdle = '';
        $navActive = 'is-active';
        $trainerNav = [
            ['label' => 'Teaching Day', 'icon' => 'fa-calendar-days', 'href' => route('trainer.dashboard'), 'active' => request()->routeIs('trainer.dashboard')],
            ['label' => 'My Trainings', 'icon' => 'fa-book-open', 'href' => route('trainer.trainings'), 'active' => request()->routeIs('trainer.trainings')],
            ['label' => 'Trainees', 'icon' => 'fa-users', 'href' => route('trainer.trainees'), 'active' => request()->routeIs('trainer.trainees')],
            ['label' => 'Sessions', 'icon' => 'fa-clipboard-list', 'href' => route('trainer.sessions'), 'active' => request()->routeIs('trainer.sessions')],
            ['label' => 'Assessments', 'icon' => 'fa-square-check', 'href' => route('trainer.assessments'), 'active' => request()->routeIs('trainer.assessments')],
            ['label' => 'Resources', 'icon' => 'fa-folder-open', 'href' => route('trainer.resources'), 'active' => request()->routeIs('trainer.resources')],
            ['label' => 'Certificates', 'icon' => 'fa-award', 'href' => route('trainer.certificates'), 'active' => request()->routeIs('trainer.certificates')],
            ['label' => 'Reports', 'icon' => 'fa-chart-column', 'href' => route('trainer.reports'), 'active' => request()->routeIs('trainer.reports')],
        ];
    @endphp

    <div class="dashboard-backdrop" data-dashboard-backdrop></div>

    <aside class="dashboard-sidebar" data-dashboard-sidebar>
        <a href="{{ route('trainer.dashboard') }}" class="dashboard-brand">
            <img src="{{ asset('assets/mcare-mark.png') }}" alt="MCARE mark" class="dashboard-brand-logo" width="44" height="44" loading="eager" decoding="sync" fetchpriority="high">
            <span class="min-w-0">
                <span class="dashboard-brand-title">MCARE Hub</span>
                <span class="dashboard-brand-subtitle">Trainer Portal</span>
            </span>
        </a>
        <button type="button" class="dashboard-menu-button absolute right-4 top-5" data-dashboard-menu-close aria-label="Close navigation">
            <x-dashboard-icon name="xmark" />
        </button>
        <button type="button" class="dashboard-sidebar-collapse" data-dashboard-sidebar-collapse aria-label="Collapse navigation" title="Collapse navigation">
            <x-dashboard-icon name="chevron-left" />
        </button>

        <nav class="dashboard-nav" aria-label="Trainer navigation">
            @foreach ($trainerNav as $item)
                <a href="{{ $item['href'] }}" data-dashboard-prefetch data-dashboard-nav-key="trainer-{{ str($item['label'])->slug() }}" class="{{ $navClass }} {{ $item['active'] ? $navActive : $navIdle }}" @if($item['active']) aria-current="page" @endif>
                    <x-dashboard-icon :name="$item['icon']" class="dashboard-nav-icon" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
            <a href="{{ route('landing') }}" class="{{ $navClass }} {{ $navIdle }}">
                <x-dashboard-icon name="arrow-up-right-from-square" class="dashboard-nav-icon" />
                <span>Public site</span>
            </a>
        </nav>

        <details class="dashboard-sidebar-footer" data-dashboard-account>
            <summary class="dashboard-account-summary">
                <span class="dashboard-account-avatar">{{ $trainerInitial }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-bold text-slate-950">{{ $trainerName }}</span>
                    <span class="block text-xs text-slate-500">Caregiving NC II Trainer</span>
                </span>
                <x-dashboard-icon name="chevron-down" class="dashboard-chevron text-xs text-slate-500 transition" />
            </summary>
            <div class="dashboard-account-menu">
                <x-dashboard-account-actions :logout-route="route('trainer.logout')" role-label="Trainer" />
            </div>
        </details>
    </aside>

    <div class="dashboard-layout">
        <header class="dashboard-topbar">
            <div class="dashboard-topbar-inner">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" class="dashboard-menu-button" data-dashboard-menu-open aria-label="Open navigation">
                        <x-dashboard-icon name="bars" /><span class="hidden sm:inline">Menu</span>
                    </button>
                    <div class="min-w-0">
                        <p class="dashboard-header-kicker">Mission Care Training Center</p>
                        <h1 class="dashboard-header-title">{{ $title ?? 'MCARE Trainer' }}</h1>
                    </div>
                </div>

                <form method="GET" action="{{ request()->routeIs('trainer.trainees') ? route('trainer.trainees') : route('trainer.dashboard') }}" class="dashboard-search">
                    <x-dashboard-icon name="magnifying-glass" class="text-sm text-slate-400" />
                    <input name="search" value="{{ request('search') }}" type="search" placeholder="Search trainees, sessions, modules..." class="min-w-0 flex-1 border-0 bg-transparent py-2.5 text-sm outline-none placeholder:text-slate-400">
                    <button type="submit" class="sr-only">Search</button>
                </form>

                <details class="relative shrink-0 justify-self-end" data-dashboard-account>
                    <summary class="dashboard-account-summary">
                        <span class="dashboard-account-avatar h-9 w-9">{{ $trainerInitial }}</span>
                        <span class="hidden max-w-32 truncate sm:block">{{ $trainerName }}</span>
                        <x-dashboard-icon name="chevron-down" class="dashboard-chevron text-xs text-slate-500 transition" />
                    </summary>
                    <div class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                        <x-dashboard-account-actions :logout-route="route('trainer.logout')" role-label="Trainer" />
                    </div>
                </details>
            </div>
            <nav class="dashboard-mobile-bar grid-cols-4" aria-label="Mobile trainer navigation">
                @foreach (array_slice($trainerNav, 0, 4) as $item)
                    <a href="{{ $item['href'] }}" data-dashboard-prefetch data-dashboard-nav-key="trainer-{{ str($item['label'])->slug() }}" class="dashboard-mobile-link {{ $item['active'] ? 'is-active' : '' }}" @if($item['active']) aria-current="page" @endif>
                        <x-dashboard-icon :name="$item['icon']" />
                        <span class="truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </header>

        <main class="dashboard-main">
            @if (session('saved'))
                <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('saved') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
