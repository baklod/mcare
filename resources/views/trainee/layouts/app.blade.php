<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'MCARE Trainee' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dashboard-shell universal-dashboard" data-dashboard-role="trainee">
    @php
        $navItem = 'dashboard-nav-link';
        $traineeName = auth()->user()?->name ?? 'Trainee';
        $traineeInitial = strtoupper(substr($traineeName, 0, 1));
        $traineeNav = [
            ['label' => 'Dashboard', 'short' => 'Home', 'icon' => 'fa-house', 'href' => '#dashboard', 'active' => true],
            ['label' => 'My Modules', 'short' => 'Modules', 'icon' => 'fa-book-open', 'href' => '#modules', 'active' => false],
            ['label' => 'Schedule', 'short' => 'Schedule', 'icon' => 'fa-calendar-days', 'href' => '#schedule', 'active' => false],
            ['label' => 'Payments', 'short' => 'Payments', 'icon' => 'fa-credit-card', 'href' => '#payments', 'active' => false],
            ['label' => 'Documents', 'short' => 'Files', 'icon' => 'fa-folder-open', 'href' => '#documents', 'active' => false],
        ];
    @endphp

    <div class="dashboard-gradient"></div>
    <div class="dashboard-backdrop" data-dashboard-backdrop></div>

    <aside class="dashboard-sidebar" data-dashboard-sidebar>
        <a href="{{ route('trainee.dashboard') }}" class="dashboard-brand">
            <img src="{{ asset('assets/mcare-mark.png') }}" alt="MCARE mark" class="dashboard-brand-logo">
            <span class="min-w-0">
                <span class="dashboard-brand-title">MCARE Hub</span>
                <span class="dashboard-brand-subtitle">Trainee Portal</span>
            </span>
        </a>
        <button type="button" class="dashboard-menu-button absolute right-4 top-5" data-dashboard-menu-close aria-label="Close navigation">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>

        <nav class="dashboard-nav" aria-label="Trainee navigation">
            <p class="dashboard-menu-label">Trainee Menu</p>
            @foreach ($traineeNav as $item)
                <a href="{{ $item['href'] }}" data-dashboard-nav-key="trainee-{{ str($item['label'])->slug() }}" class="{{ $navItem }} {{ $item['active'] ? 'is-active' : '' }}" @if($item['active']) aria-current="page" @endif>
                    <i class="dashboard-nav-icon fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <details class="dashboard-sidebar-footer" data-dashboard-account>
            <summary class="dashboard-account-summary">
                <span class="dashboard-account-avatar">{{ $traineeInitial }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-bold text-slate-950">{{ $traineeName }}</span>
                    <span class="block text-xs text-slate-500">{{ \App\Support\AccountPortal::roleLabelFor(auth()->user()) }}</span>
                </span>
                <i class="dashboard-chevron fa-solid fa-chevron-down text-xs text-slate-400 transition" aria-hidden="true"></i>
            </summary>
            <div class="dashboard-account-menu">
                <form method="POST" action="{{ route('trainee.logout') }}">
                    @csrf
                    <button type="submit" class="dashboard-account-action is-danger">Sign out</button>
                </form>
            </div>
        </details>
    </aside>

    <div class="dashboard-layout">
        <header class="dashboard-topbar">
            <div class="dashboard-topbar-inner">
                <div class="flex items-center gap-3">
                    <button type="button" class="dashboard-menu-button" data-dashboard-menu-open aria-label="Open navigation">
                        <i class="fa-solid fa-bars" aria-hidden="true"></i><span class="hidden sm:inline">Menu</span>
                    </button>
                    <div class="min-w-0">
                        <p class="dashboard-header-kicker">Caregiving NC II Program</p>
                        <h1 class="dashboard-header-title">{{ $title ?? 'Trainee Portal' }}</h1>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <details class="relative shrink-0 justify-self-end" data-dashboard-account>
                        <summary class="dashboard-account-summary">
                        <span class="dashboard-account-avatar h-9 w-9">{{ $traineeInitial }}</span>
                        <span class="hidden text-left sm:block">
                            <span class="block text-sm font-bold">{{ $traineeName }}</span>
                            <span class="block text-xs font-semibold text-slate-400">{{ \App\Support\AccountPortal::roleLabelFor(auth()->user()) }}</span>
                        </span>
                        <i class="dashboard-chevron fa-solid fa-chevron-down text-xs text-slate-400 transition" aria-hidden="true"></i>
                        </summary>
                        <div class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                            <form method="POST" action="{{ route('trainee.logout') }}">
                                @csrf
                                <button type="submit" class="dashboard-account-action is-danger">Sign out</button>
                            </form>
                        </div>
                    </details>
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

    <nav class="dashboard-mobile-bar grid-cols-4" aria-label="Mobile trainee navigation">
        @foreach (array_slice($traineeNav, 0, 4) as $item)
            <a href="{{ $item['href'] }}" data-dashboard-nav-key="trainee-{{ str($item['label'])->slug() }}" class="dashboard-mobile-link {{ $item['active'] ? 'is-active' : '' }}" @if($item['active']) aria-current="page" @endif>
                <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                <span class="truncate">{{ $item['short'] }}</span>
            </a>
        @endforeach
    </nav>
</body>
</html>
