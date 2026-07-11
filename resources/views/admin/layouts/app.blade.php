<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#faf9f7]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'MCARE Admin' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dashboard-shell universal-dashboard" data-dashboard-role="admin">
    @php
        $adminName = auth()->user()?->name ?? 'Admin User';
        $adminInitial = strtoupper(substr($adminName, 0, 1));
        $adminBrandAsset = request()->routeIs('admin.login') ? 'assets/official-logo.png' : 'assets/mcare-mark.png';
        $adminBrandAlt = request()->routeIs('admin.login') ? 'Mission Care Training Center logo' : 'MCARE mark';
        $navClass = 'dashboard-nav-link';
        $navIdle = '';
        $navActive = 'is-active';

        $primaryNav = [
            ['label' => 'Dashboard', 'icon' => 'fa-gauge-high', 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
            ['label' => 'Applications', 'icon' => 'fa-user-check', 'href' => route('admin.enrollments.index'), 'active' => request()->routeIs('admin.enrollments.*')],
            ['label' => 'Payments', 'icon' => 'fa-credit-card', 'href' => route('admin.payment-schedules.index'), 'active' => request()->routeIs('admin.payment-schedules.*')],
            ['label' => 'Schedules', 'icon' => 'fa-calendar-days', 'href' => route('admin.schedules.index'), 'active' => request()->routeIs('admin.schedules.*')],
        ];

        $capstoneNav = [
            ['label' => 'Trainees', 'icon' => 'fa-users', 'href' => route('admin.dashboard').'#action-queue'],
            ['label' => 'LMS Modules', 'icon' => 'fa-book-open', 'href' => route('admin.dashboard').'#lms-modules'],
            ['label' => 'Certificates', 'icon' => 'fa-award', 'href' => route('admin.dashboard').'#certificates'],
            ['label' => 'Alumni Jobs', 'icon' => 'fa-briefcase', 'href' => route('admin.dashboard').'#reports'],
            ['label' => 'Reports', 'icon' => 'fa-chart-column', 'href' => route('admin.dashboard').'#reports'],
        ];
    @endphp

    <div class="dashboard-backdrop" data-dashboard-backdrop></div>

    <aside class="dashboard-sidebar" data-dashboard-sidebar>
        <a href="{{ route('admin.dashboard') }}" class="dashboard-brand">
            <img src="{{ asset($adminBrandAsset) }}" alt="{{ $adminBrandAlt }}" class="dashboard-brand-logo">
            <span class="min-w-0">
                <span class="dashboard-brand-title">MCARE Hub</span>
                <span class="dashboard-brand-subtitle">Admin Portal</span>
            </span>
        </a>
        <button type="button" class="dashboard-menu-button absolute right-4 top-5" data-dashboard-menu-close aria-label="Close navigation">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>

        <nav class="dashboard-nav" aria-label="Admin navigation">
            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Operations</p>
                <div class="mt-2 space-y-1">
                    @foreach ($primaryNav as $item)
                        <a href="{{ $item['href'] }}" data-dashboard-nav-key="admin-{{ str($item['label'])->slug() }}" class="{{ $navClass }} {{ $item['active'] ? $navActive : $navIdle }}" @if($item['active']) aria-current="page" @endif>
                            <i class="dashboard-nav-icon fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Learning system</p>
                <div class="mt-2 space-y-1">
                    @foreach ($capstoneNav as $item)
                        <a href="{{ $item['href'] }}" data-dashboard-nav-key="admin-{{ str($item['label'])->slug() }}" class="{{ $navClass }} {{ $navIdle }}">
                            <i class="dashboard-nav-icon fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="border-t border-slate-200 pt-4">
                <a href="{{ route('admin.logs.index') }}" class="{{ $navClass }} {{ request()->routeIs('admin.logs.*') ? $navActive : $navIdle }}">
                    <i class="dashboard-nav-icon fa-solid fa-shield-halved" aria-hidden="true"></i>
                    <span>Admin logs</span>
                </a>
                <a href="{{ route('landing') }}" class="{{ $navClass }} {{ $navIdle }}">
                    <i class="dashboard-nav-icon fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                    <span>Public site</span>
                </a>
            </div>
        </nav>

        <details class="dashboard-sidebar-footer" data-dashboard-account>
            <summary class="dashboard-account-summary">
                <span class="dashboard-account-avatar">{{ $adminInitial }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-bold text-slate-950">{{ $adminName }}</span>
                    <span class="block text-xs text-slate-500">Administrator</span>
                </span>
                <i class="dashboard-chevron fa-solid fa-chevron-up text-xs text-slate-400 transition" aria-hidden="true"></i>
            </summary>
            <div class="dashboard-account-menu">
                <a href="{{ route('admin.logs.index') }}" class="dashboard-account-action">Admin logs</a>
                @auth
                    <form method="POST" action="{{ route('admin.logout') }}" class="mt-1 border-t border-slate-100 pt-1">
                        @csrf
                        <button type="submit" class="dashboard-account-action is-danger">Sign out</button>
                    </form>
                @endauth
            </div>
        </details>
    </aside>

    <div class="dashboard-layout">
        <header class="dashboard-topbar">
            <div class="dashboard-topbar-inner">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" class="dashboard-menu-button" data-dashboard-menu-open aria-label="Open navigation">
                        <i class="fa-solid fa-bars" aria-hidden="true"></i><span class="hidden sm:inline">Menu</span>
                    </button>
                    <div class="min-w-0">
                        <p class="dashboard-header-kicker">Mission Care Training Center</p>
                        <h1 class="dashboard-header-title">{{ $title ?? 'MCARE Admin' }}</h1>
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.enrollments.index') }}" class="dashboard-search">
                    <i class="fa-solid fa-magnifying-glass text-sm text-slate-400" aria-hidden="true"></i>
                    <input name="search" type="search" placeholder="Search applicants..." class="min-w-0 flex-1 border-0 bg-transparent py-2.5 text-sm outline-none placeholder:text-slate-400">
                    <button type="submit" class="sr-only">Search</button>
                </form>

                <details class="relative shrink-0 justify-self-end" data-dashboard-account>
                        <summary class="dashboard-account-summary">
                            <span class="dashboard-account-avatar h-9 w-9">{{ $adminInitial }}</span>
                            <span class="hidden max-w-36 truncate sm:block">{{ $adminName }}</span>
                            <i class="dashboard-chevron fa-solid fa-chevron-down text-xs text-slate-400 transition" aria-hidden="true"></i>
                        </summary>
                        <div class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                            <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Active admin</p>
                            <a href="{{ route('admin.logs.index') }}" class="dashboard-account-action">Admin logs</a>
                            @auth
                                <form method="POST" action="{{ route('admin.logout') }}" class="mt-1 border-t border-slate-100 pt-1">
                                    @csrf
                                    <button type="submit" class="dashboard-account-action is-danger">Sign out</button>
                                </form>
                            @endauth
                        </div>
                </details>
            </div>

            <nav class="dashboard-mobile-bar grid-cols-4" aria-label="Mobile admin navigation">
                @foreach ($primaryNav as $item)
                    <a href="{{ $item['href'] }}" data-dashboard-nav-key="admin-{{ str($item['label'])->slug() }}" class="dashboard-mobile-link {{ $item['active'] ? 'is-active' : '' }}" @if($item['active']) aria-current="page" @endif>
                        <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
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
