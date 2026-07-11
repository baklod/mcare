<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#faf9f7]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'MCARE Trainer' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dashboard-shell universal-dashboard" data-dashboard-role="trainer">
    @php
        $trainerName = auth()->user()?->name ?? 'Trainer User';
        $trainerInitial = strtoupper(substr($trainerName, 0, 1));
        $navClass = 'dashboard-nav-link';
        $navIdle = '';
        $navActive = 'is-active';
        $trainerNav = [
            ['label' => 'Teaching Day', 'icon' => 'fa-calendar-days', 'href' => route('trainer.dashboard'), 'active' => request()->routeIs('trainer.dashboard')],
            ['label' => 'My Trainings', 'icon' => 'fa-book-open', 'href' => route('trainer.dashboard').'#modules', 'active' => false],
            ['label' => 'Trainees', 'icon' => 'fa-users', 'href' => route('trainer.dashboard').'#learner-follow-up', 'active' => false],
            ['label' => 'Sessions', 'icon' => 'fa-clipboard-list', 'href' => route('trainer.dashboard').'#teaching-timeline', 'active' => false],
            ['label' => 'Assessments', 'icon' => 'fa-square-check', 'href' => route('trainer.dashboard').'#learner-follow-up', 'active' => false],
            ['label' => 'Resources', 'icon' => 'fa-folder-open', 'href' => route('trainer.dashboard').'#modules', 'active' => false],
            ['label' => 'Certificates', 'icon' => 'fa-award', 'href' => route('trainer.dashboard').'#modules', 'active' => false],
            ['label' => 'Reports', 'icon' => 'fa-chart-column', 'href' => route('trainer.dashboard').'#learner-follow-up', 'active' => false],
        ];
    @endphp

    <div class="dashboard-backdrop" data-dashboard-backdrop></div>

    <aside class="dashboard-sidebar" data-dashboard-sidebar>
        <a href="{{ route('trainer.dashboard') }}" class="dashboard-brand">
            <img src="{{ asset('assets/mcare-mark.png') }}" alt="MCARE mark" class="dashboard-brand-logo">
            <span class="min-w-0">
                <span class="dashboard-brand-title">MCARE Hub</span>
                <span class="dashboard-brand-subtitle">Trainer Portal</span>
            </span>
        </a>
        <button type="button" class="dashboard-menu-button absolute right-4 top-5" data-dashboard-menu-close aria-label="Close navigation">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>

        <nav class="dashboard-nav" aria-label="Trainer navigation">
            @foreach ($trainerNav as $item)
                <a href="{{ $item['href'] }}" data-dashboard-nav-key="trainer-{{ str($item['label'])->slug() }}" class="{{ $navClass }} {{ $item['active'] ? $navActive : $navIdle }}" @if($item['active']) aria-current="page" @endif>
                    <i class="dashboard-nav-icon fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
            <a href="{{ route('landing') }}" class="{{ $navClass }} {{ $navIdle }}">
                <i class="dashboard-nav-icon fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
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
                <i class="dashboard-chevron fa-solid fa-chevron-down text-xs text-slate-500 transition" aria-hidden="true"></i>
            </summary>
            <div class="dashboard-account-menu">
                @if (auth()->user()?->role === 'trainer')
                    <form method="POST" action="{{ route('trainer.logout') }}">
                        @csrf
                        <button type="submit" class="dashboard-account-action is-danger">Sign out</button>
                    </form>
                @endif
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
                        <h1 class="dashboard-header-title">{{ $title ?? 'MCARE Trainer' }}</h1>
                    </div>
                </div>

                <form method="GET" action="{{ route('trainer.dashboard') }}" class="dashboard-search">
                    <i class="fa-solid fa-magnifying-glass text-sm text-slate-400" aria-hidden="true"></i>
                    <input name="search" value="{{ request('search') }}" type="search" placeholder="Search trainees, sessions, modules..." class="min-w-0 flex-1 border-0 bg-transparent py-2.5 text-sm outline-none placeholder:text-slate-400">
                    <button type="submit" class="sr-only">Search</button>
                </form>

                <details class="relative shrink-0 justify-self-end" data-dashboard-account>
                    <summary class="dashboard-account-summary">
                        <span class="dashboard-account-avatar h-9 w-9">{{ $trainerInitial }}</span>
                        <span class="hidden max-w-32 truncate sm:block">{{ $trainerName }}</span>
                        <i class="dashboard-chevron fa-solid fa-chevron-down text-xs text-slate-500 transition" aria-hidden="true"></i>
                    </summary>
                    <div class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                        @if (auth()->user()?->role === 'trainer')
                            <form method="POST" action="{{ route('trainer.logout') }}">
                                @csrf
                                <button type="submit" class="dashboard-account-action is-danger">Sign out</button>
                            </form>
                        @endif
                    </div>
                </details>
            </div>
            <nav class="dashboard-mobile-bar grid-cols-4" aria-label="Mobile trainer navigation">
                @foreach (array_slice($trainerNav, 0, 4) as $item)
                    <a href="{{ $item['href'] }}" data-dashboard-nav-key="trainer-{{ str($item['label'])->slug() }}" class="dashboard-mobile-link {{ $item['active'] ? 'is-active' : '' }}" @if($item['active']) aria-current="page" @endif>
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
