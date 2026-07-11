<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#faf9f7]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'MCARE Trainer' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#faf9f7] font-sans text-slate-900 antialiased">
    @php
        $trainerName = auth()->user()?->name ?? 'Trainer User';
        $trainerInitial = strtoupper(substr($trainerName, 0, 1));
        $navClass = 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition';
        $navIdle = 'text-slate-600 hover:bg-slate-100 hover:text-slate-950';
        $navActive = 'bg-purple-50 text-purple-700 ring-1 ring-inset ring-purple-100';
        $trainerNav = [
            ['label' => 'Teaching Day', 'icon' => 'fa-calendar-days', 'href' => route('trainer.dashboard'), 'active' => request()->routeIs('trainer.dashboard')],
            ['label' => 'My Trainings', 'icon' => 'fa-book-open', 'href' => route('trainer.dashboard').'#module-checklist', 'active' => false],
            ['label' => 'Trainees', 'icon' => 'fa-users', 'href' => route('trainer.dashboard').'#learner-follow-up', 'active' => false],
            ['label' => 'Sessions', 'icon' => 'fa-clipboard-list', 'href' => route('trainer.dashboard').'#teaching-timeline', 'active' => false],
            ['label' => 'Assessments', 'icon' => 'fa-square-check', 'href' => route('trainer.dashboard').'#learner-follow-up', 'active' => false],
            ['label' => 'Resources', 'icon' => 'fa-folder-open', 'href' => route('trainer.dashboard').'#module-checklist', 'active' => false],
            ['label' => 'Certificates', 'icon' => 'fa-award', 'href' => route('trainer.dashboard').'#module-checklist', 'active' => false],
            ['label' => 'Reports', 'icon' => 'fa-chart-column', 'href' => route('trainer.dashboard').'#learner-follow-up', 'active' => false],
        ];
    @endphp

    <aside class="fixed inset-y-0 left-0 z-30 hidden w-60 border-r border-slate-200 bg-white px-4 py-6 lg:flex lg:flex-col">
        <a href="{{ route('trainer.dashboard') }}" class="flex items-center gap-3 px-2">
            <img src="{{ asset('assets/official-logo.png') }}" alt="Mission Care Training Center logo" class="h-12 w-12 object-contain">
            <span class="min-w-0">
                <span class="block text-base font-bold tracking-tight text-slate-950">MCARE Hub</span>
                <span class="block text-xs font-semibold uppercase tracking-wide text-purple-700">Trainer</span>
            </span>
        </a>

        <nav class="mt-10 flex-1 space-y-1 overflow-y-auto">
            @foreach ($trainerNav as $item)
                <a href="{{ $item['href'] }}" class="{{ $navClass }} {{ $item['active'] ? $navActive : $navIdle }}">
                    <i class="fa-solid {{ $item['icon'] }} w-4 text-center" aria-hidden="true"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
            <a href="{{ route('landing') }}" class="{{ $navClass }} {{ $navIdle }}">
                <i class="fa-solid fa-arrow-up-right-from-square w-4 text-center" aria-hidden="true"></i>
                <span>Public site</span>
            </a>
        </nav>

        <details class="relative mt-6 border-t border-slate-200 pt-5">
            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl p-2 text-left hover:bg-slate-50">
                <span class="grid h-10 w-10 place-items-center rounded-full bg-purple-100 text-sm font-bold text-purple-700">{{ $trainerInitial }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-bold text-slate-950">{{ $trainerName }}</span>
                    <span class="block text-xs text-slate-500">Caregiving NC II Trainer</span>
                </span>
                <i class="fa-solid fa-chevron-down text-xs text-slate-500" aria-hidden="true"></i>
            </summary>
            <div class="absolute bottom-full left-0 mb-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                @if (auth()->user()?->role === 'trainer')
                    <form method="POST" action="{{ route('trainer.logout') }}">
                        @csrf
                        <button type="submit" class="w-full rounded-lg px-3 py-2.5 text-left text-sm font-semibold text-red-600 hover:bg-red-50">Sign out</button>
                    </form>
                @endif
            </div>
        </details>
    </aside>

    <div class="lg:pl-60">
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white">
            <div class="flex min-h-20 items-center justify-between gap-4 px-5 py-3 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <a href="{{ route('trainer.dashboard') }}" class="lg:hidden">
                        <img src="{{ asset('assets/official-logo.png') }}" alt="Mission Care Training Center logo" class="h-10 w-10 object-contain">
                    </a>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-purple-700">Mission Care Training Center</p>
                        <h1 class="truncate text-lg font-bold text-slate-950">{{ $title ?? 'MCARE Trainer' }}</h1>
                    </div>
                </div>

                <form method="GET" action="{{ route('trainer.dashboard') }}" class="hidden w-full max-w-sm items-center gap-3 rounded-lg border border-slate-300 bg-white px-3 md:flex">
                    <i class="fa-solid fa-magnifying-glass text-sm text-slate-400" aria-hidden="true"></i>
                    <input name="search" value="{{ request('search') }}" type="search" placeholder="Search trainees, sessions, modules..." class="min-w-0 flex-1 border-0 bg-transparent py-2.5 text-sm outline-none placeholder:text-slate-400">
                    <button type="submit" class="sr-only">Search</button>
                </form>

                <details class="relative shrink-0">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-2 py-1.5 text-sm font-semibold hover:bg-slate-50">
                        <span class="grid h-9 w-9 place-items-center rounded-full bg-purple-100 text-purple-700">{{ $trainerInitial }}</span>
                        <span class="hidden max-w-32 truncate sm:block">{{ $trainerName }}</span>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-500" aria-hidden="true"></i>
                    </summary>
                    <div class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                        @if (auth()->user()?->role === 'trainer')
                            <form method="POST" action="{{ route('trainer.logout') }}">
                                @csrf
                                <button type="submit" class="w-full rounded-lg px-3 py-2.5 text-left text-sm font-semibold text-red-600 hover:bg-red-50">Sign out</button>
                            </form>
                        @endif
                    </div>
                </details>
            </div>
            <nav class="flex items-center justify-between gap-0 overflow-hidden border-t border-slate-100 px-3 py-2 lg:hidden">
                @foreach (array_slice($trainerNav, 0, 4) as $item)
                    <a href="{{ $item['href'] }}" class="shrink-0 rounded-lg px-2 py-2 text-xs font-semibold {{ $item['active'] ? 'bg-purple-50 text-purple-700' : 'text-slate-600' }}">{{ $item['label'] }}</a>
                @endforeach
            </nav>
        </header>

        <main class="mx-auto max-w-[1440px] px-4 py-7 sm:px-6 lg:px-8 lg:py-9">
            @if (session('saved'))
                <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('saved') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
