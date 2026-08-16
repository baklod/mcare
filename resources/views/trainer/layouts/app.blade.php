<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#faf9f7]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'MCARE Trainer' }}</title>
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
        $trainerStreamHref = \Illuminate\Support\Facades\Route::has('trainer.stream')
            ? route('trainer.stream')
            : route('trainer.dashboard');
        $trainerQuizCreateHref = \Illuminate\Support\Facades\Route::has('trainer.quizzes.create')
            ? route('trainer.quizzes.create')
            : route('trainer.assessments');
        $trainerPrimaryNav = [
            ['label' => 'Stream', 'short' => 'Stream', 'icon' => 'fa-bell', 'href' => $trainerStreamHref, 'active' => request()->routeIs('trainer.stream', 'trainer.announcements.*')],
            ['label' => 'Classwork', 'short' => 'Classwork', 'icon' => 'fa-book-open', 'href' => route('trainer.resources'), 'active' => request()->routeIs('trainer.resources', 'trainer.modules.*')],
            ['label' => 'Quizzes', 'short' => 'Quizzes', 'icon' => 'fa-square-check', 'href' => route('trainer.assessments'), 'active' => request()->routeIs('trainer.assessments', 'trainer.quizzes.*')],
            ['label' => 'Calendar', 'short' => 'Calendar', 'icon' => 'fa-calendar-days', 'href' => route('trainer.sessions'), 'active' => request()->routeIs('trainer.sessions')],
        ];
        $trainerSecondaryNav = [
            ['label' => 'Teaching Day', 'icon' => 'fa-gauge-high', 'href' => route('trainer.dashboard'), 'active' => request()->routeIs('trainer.dashboard')],
            ['label' => 'Classes', 'icon' => 'fa-folder-open', 'href' => route('trainer.trainings'), 'active' => request()->routeIs('trainer.trainings')],
            ['label' => 'People', 'icon' => 'fa-users', 'href' => route('trainer.trainees'), 'active' => request()->routeIs('trainer.trainees')],
            ['label' => 'Certificates', 'icon' => 'fa-award', 'href' => route('trainer.certificates'), 'active' => request()->routeIs('trainer.certificates')],
            ['label' => 'Reports', 'icon' => 'fa-chart-column', 'href' => route('trainer.reports'), 'active' => request()->routeIs('trainer.reports')],
        ];
    @endphp

    <aside class="dashboard-sidebar" data-dashboard-sidebar>
        <div class="flex min-h-11 items-center border-b border-slate-100 pb-3">
            <div class="dashboard-brand flex-1 min-w-0">
                <span class="min-w-0">
                    <span class="dashboard-brand-title">MCARE Hub</span>
                    <span class="dashboard-brand-subtitle">Trainer Portal</span>
                </span>
            </div>
        </div>

        <nav class="dashboard-nav" aria-label="Trainer navigation">
            <p class="dashboard-menu-label">Classroom</p>
            @foreach ($trainerPrimaryNav as $item)
                <a href="{{ $item['href'] }}" data-dashboard-prefetch data-dashboard-nav-key="trainer-{{ str($item['label'])->slug() }}" class="{{ $navClass }} {{ $item['active'] ? $navActive : $navIdle }}" @if($item['active']) aria-current="page" @endif>
                    <x-dashboard-icon :name="$item['icon']" class="dashboard-nav-icon" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
            <p class="dashboard-menu-label">Teaching tools</p>
            @foreach ($trainerSecondaryNav as $item)
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
                    <div class="min-w-0">
                        <p class="dashboard-header-kicker">Mission Care Training Center</p>
                        <h1 class="dashboard-header-title">
                            <span class="dashboard-title-desktop">{{ $title ?? 'MCARE Trainer' }}</span>
                            <span class="dashboard-title-mobile">{{ str($title ?? 'MCARE Trainer')->before('|')->trim() }}</span>
                        </h1>
                    </div>
                </div>

                <div class="dashboard-classroom-actions">
                    <a href="{{ $trainerStreamHref }}" class="dashboard-context-link">Open stream</a>
                    <a href="{{ $trainerQuizCreateHref }}" class="dashboard-context-link is-primary">
                        <span aria-hidden="true">+</span>
                        <span>Create quiz</span>
                    </a>
                </div>

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
                @foreach ($trainerPrimaryNav as $item)
                    <a href="{{ $item['href'] }}" data-dashboard-prefetch data-dashboard-nav-key="trainer-{{ str($item['label'])->slug() }}" class="dashboard-mobile-link {{ $item['active'] ? 'is-active' : '' }}" @if($item['active']) aria-current="page" @endif>
                        <x-dashboard-icon :name="$item['icon']" />
                        <span class="truncate">{{ $item['short'] }}</span>
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

    <dialog class="lms-confirm-dialog" data-lms-confirm-dialog aria-labelledby="lms-confirm-title">
        <form method="dialog" class="lms-confirm-card">
            <span class="lms-confirm-icon" aria-hidden="true">!</span>
            <h2 id="lms-confirm-title">Confirm action</h2>
            <p data-lms-confirm-message>This action cannot be undone.</p>
            <div class="lms-confirm-actions">
                <button value="cancel" class="secondary-action">Cancel</button>
                <button value="confirm" class="lms-danger-action">Continue</button>
            </div>
        </form>
    </dialog>
</body>
</html>
