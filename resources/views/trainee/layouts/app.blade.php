<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'MCARE Trainee' }}</title>
    <x-dashboard-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dashboard-shell universal-dashboard" data-dashboard-role="trainee">
    <div class="dashboard-navigation-progress" aria-hidden="true"></div>
    @php
        $navItem = 'dashboard-nav-link';
        $traineeName = auth()->user()?->name ?? 'Trainee';
        $traineeInitial = strtoupper(substr($traineeName, 0, 1));
        $traineeStreamHref = \Illuminate\Support\Facades\Route::has('trainee.stream')
            ? route('trainee.stream')
            : route('trainee.dashboard');
        $traineeQuizHref = \Illuminate\Support\Facades\Route::has('trainee.quizzes.index')
            ? route('trainee.quizzes.index')
            : route('trainee.modules.index');
        $traineePrimaryNav = [
            ['label' => 'Stream', 'short' => 'Stream', 'icon' => 'fa-bell', 'href' => $traineeStreamHref, 'active' => request()->routeIs('trainee.stream')],
            ['label' => 'Classwork', 'short' => 'Classwork', 'icon' => 'fa-book-open', 'href' => route('trainee.modules.index'), 'active' => request()->routeIs('trainee.modules.*')],
            ['label' => 'Quizzes', 'short' => 'Quizzes', 'icon' => 'fa-square-check', 'href' => $traineeQuizHref, 'active' => request()->routeIs('trainee.quizzes.*', 'trainee.quiz-attempts.*')],
            ['label' => 'Calendar', 'short' => 'Calendar', 'icon' => 'fa-calendar-days', 'href' => route('trainee.schedule'), 'active' => request()->routeIs('trainee.schedule')],
        ];
        $traineeSecondaryNav = [
            ['label' => 'Home', 'icon' => 'fa-house', 'href' => route('trainee.dashboard'), 'active' => request()->routeIs('trainee.dashboard')],
            ['label' => 'Payments', 'icon' => 'fa-credit-card', 'href' => route('trainee.payments'), 'active' => request()->routeIs('trainee.payments')],
            ['label' => 'Documents', 'icon' => 'fa-folder-open', 'href' => route('trainee.documents'), 'active' => request()->routeIs('trainee.documents')],
        ];
    @endphp

    <div class="dashboard-gradient"></div>

    <aside class="dashboard-sidebar" data-dashboard-sidebar>
        <div class="flex min-h-11 items-center border-b border-slate-100 pb-3">
            <div class="dashboard-brand flex-1 min-w-0">
                <span class="min-w-0">
                    <span class="dashboard-brand-title">MCARE Hub</span>
                    <span class="dashboard-brand-subtitle">Trainee Portal</span>
                </span>
            </div>
        </div>

        <nav class="dashboard-nav" aria-label="Trainee navigation">
            <p class="dashboard-menu-label">Classroom</p>
            @foreach ($traineePrimaryNav as $item)
                <a href="{{ $item['href'] }}" data-dashboard-prefetch data-dashboard-nav-key="trainee-{{ str($item['label'])->slug() }}" class="{{ $navItem }} {{ $item['active'] ? 'is-active' : '' }}" @if($item['active']) aria-current="page" @endif>
                    <x-dashboard-icon :name="$item['icon']" class="dashboard-nav-icon" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
            <p class="dashboard-menu-label">My account</p>
            @foreach ($traineeSecondaryNav as $item)
                <a href="{{ $item['href'] }}" data-dashboard-prefetch data-dashboard-nav-key="trainee-{{ str($item['label'])->slug() }}" class="{{ $navItem }} {{ $item['active'] ? 'is-active' : '' }}" @if($item['active']) aria-current="page" @endif>
                    <x-dashboard-icon :name="$item['icon']" class="dashboard-nav-icon" />
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
                <x-dashboard-icon name="chevron-down" class="dashboard-chevron text-xs text-slate-400 transition" />
            </summary>
            <div class="dashboard-account-menu">
                <x-dashboard-account-actions :logout-route="route('trainee.logout')" role-label="Trainee" />
            </div>
        </details>
    </aside>

    <div class="dashboard-layout">
        <header class="dashboard-topbar">
            <div class="dashboard-topbar-inner">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="min-w-0">
                        <p class="dashboard-header-kicker">Caregiving NC II Program</p>
                        <h1 class="dashboard-header-title">
                            <span class="dashboard-title-desktop">{{ $title ?? 'Trainee Portal' }}</span>
                            <span class="dashboard-title-mobile">{{ str($title ?? 'Trainee Portal')->before('|')->trim() }}</span>
                        </h1>
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
                        <x-dashboard-icon name="chevron-down" class="dashboard-chevron text-xs text-slate-400 transition" />
                        </summary>
                        <div class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                            <x-dashboard-account-actions :logout-route="route('trainee.logout')" role-label="Trainee" />
                        </div>
                    </details>
                </div>
            </div>
        </header>

        <main class="dashboard-main pb-28 lg:pb-9">
            @if (session('saved'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold leading-6 text-emerald-700" role="status" aria-live="polite" data-auto-dismiss="5000">
                    {{ session('saved') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <nav class="dashboard-mobile-bar grid-cols-4" aria-label="Mobile trainee navigation">
        @foreach ($traineePrimaryNav as $item)
            <a href="{{ $item['href'] }}" data-dashboard-prefetch data-dashboard-nav-key="trainee-{{ str($item['label'])->slug() }}" class="dashboard-mobile-link {{ $item['active'] ? 'is-active' : '' }}" @if($item['active']) aria-current="page" @endif>
                <x-dashboard-icon :name="$item['icon']" />
                <span class="truncate">{{ $item['short'] }}</span>
            </a>
        @endforeach
    </nav>

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
