<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'MCARE Trainer' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    @php
        $trainerName = auth()->user()?->name ?? 'Trainer User';
        $trainerInitial = strtoupper(substr($trainerName, 0, 1));

        $navClass = 'flex items-center justify-between gap-3 rounded-2xl border px-4 py-3 text-sm font-black transition';
        $navIdle = 'border-transparent text-slate-600 hover:border-purple-100 hover:bg-purple-50 hover:text-purple-700';
        $navActive = 'border-purple-100 bg-purple-50 text-purple-700 shadow-sm';

        $trainerNav = [
            ['label' => 'Dashboard', 'meta' => 'Today', 'href' => route('trainer.dashboard'), 'active' => request()->routeIs('trainer.dashboard')],
            ['label' => 'My Trainings', 'meta' => 'LMS', 'href' => route('trainer.dashboard').'#training-overview', 'active' => false],
            ['label' => 'Trainees', 'meta' => 'Progress', 'href' => route('trainer.dashboard').'#trainee-progress', 'active' => false],
            ['label' => 'Sessions', 'meta' => 'Schedule', 'href' => route('trainer.dashboard').'#today-schedule', 'active' => false],
            ['label' => 'Assessments', 'meta' => 'Marks', 'href' => route('trainer.dashboard').'#training-overview', 'active' => false],
            ['label' => 'Resources', 'meta' => 'Secure', 'href' => route('trainer.dashboard').'#resources', 'active' => false],
            ['label' => 'Reports', 'meta' => 'Analytics', 'href' => route('trainer.dashboard').'#trainer-reports', 'active' => false],
            ['label' => 'Certificates', 'meta' => 'Ready', 'href' => route('trainer.dashboard').'#trainer-reports', 'active' => false],
        ];
    @endphp

    <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-96 bg-gradient-to-b from-purple-100/80 via-purple-50/60 to-transparent"></div>
    <div class="pointer-events-none fixed inset-x-0 bottom-0 -z-10 h-80 bg-gradient-to-t from-purple-100/60 via-slate-50 to-transparent"></div>

    <aside class="fixed inset-y-0 left-0 z-30 hidden w-[19rem] border-r border-purple-100 bg-white/95 px-5 py-6 shadow-2xl shadow-slate-200/70 backdrop-blur-xl lg:flex lg:flex-col">
        <a href="{{ route('trainer.dashboard') }}" class="flex items-center gap-4 px-2">
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-purple-50 shadow-lg shadow-purple-100">
                <img src="{{ asset('assets/official-logo.png') }}" alt="Mission Care Training Center logo" class="h-11 w-11 object-contain">
            </span>
            <span>
                <span class="block text-base font-black tracking-wide text-slate-950">MCARE Hub</span>
                <span class="block text-xs font-bold uppercase tracking-wide text-purple-600">Trainer Panel</span>
            </span>
        </a>

        <nav class="mt-9 flex-1 space-y-2 overflow-y-auto pr-1">
            @foreach ($trainerNav as $item)
                <a href="{{ $item['href'] }}" class="{{ $navClass }} {{ $item['active'] ? $navActive : $navIdle }}">
                    <span>{{ $item['label'] }}</span>
                    <span class="rounded-full bg-white px-2.5 py-1 text-[11px] text-slate-500 ring-1 ring-slate-100">{{ $item['meta'] }}</span>
                </a>
            @endforeach

            <a href="{{ route('landing') }}" class="{{ $navClass }} {{ $navIdle }}">
                <span>Public Site</span>
                <span class="rounded-full bg-white px-2.5 py-1 text-[11px] text-slate-500 ring-1 ring-slate-100">Home</span>
            </a>
        </nav>

        <div class="mt-6 rounded-3xl border border-slate-100 bg-slate-50 p-4">
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-full bg-purple-100 text-sm font-black text-purple-700">{{ $trainerInitial }}</span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-black text-slate-950">{{ $trainerName }}</p>
                    <p class="text-xs font-semibold text-slate-500">Caring to teach, teaching to care.</p>
                </div>
            </div>
        </div>
    </aside>

    <div class="lg:pl-[19rem]">
        <header class="sticky top-0 z-20 border-b border-purple-100 bg-white/90 backdrop-blur-xl">
            <div class="flex min-h-20 flex-col gap-4 px-5 py-4 xl:flex-row xl:items-center xl:justify-between lg:px-8">
                <div class="flex min-w-0 items-center gap-4">
                    <a href="{{ route('trainer.dashboard') }}" class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-white shadow-lg shadow-purple-100 lg:hidden">
                        <img src="{{ asset('assets/official-logo.png') }}" alt="Mission Care Training Center logo" class="h-9 w-9 object-contain">
                    </a>
                    <div class="min-w-0">
                        <p class="text-xs font-black uppercase tracking-wide text-purple-600">Mission Care Training Center</p>
                        <h1 class="mt-1 truncate text-xl font-black text-slate-950">{{ $title ?? 'MCARE Trainer' }}</h1>
                    </div>
                </div>

                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div class="flex gap-2 overflow-x-auto pb-1 lg:hidden">
                        @foreach (array_slice($trainerNav, 0, 4) as $item)
                            <a href="{{ $item['href'] }}" class="shrink-0 rounded-full border px-4 py-2.5 text-sm font-black {{ $item['active'] ? 'border-purple-200 bg-purple-50 text-purple-700' : 'border-slate-200 bg-white text-slate-700' }}">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>

                    <form method="GET" action="{{ route('trainer.dashboard') }}" class="hidden min-w-80 items-center rounded-full border border-slate-200 bg-white px-4 shadow-sm xl:flex">
                        <input name="search" type="search" placeholder="Search trainee, module, session..." class="min-w-0 flex-1 border-0 bg-transparent py-3 text-sm text-slate-700 outline-none placeholder:text-slate-400">
                        <button class="text-sm font-black text-purple-700">Search</button>
                    </form>

                    <details class="relative">
                        <summary class="flex cursor-pointer list-none items-center gap-3 rounded-full border border-slate-200 bg-white py-2 pl-3 pr-4 text-sm font-black text-slate-700 shadow-sm hover:border-purple-200 hover:text-purple-700">
                            <span class="grid h-9 w-9 place-items-center rounded-full bg-purple-50 text-purple-700">{{ $trainerInitial }}</span>
                            <span class="max-w-40 truncate">{{ $trainerName }}</span>
                            <span class="text-xs">v</span>
                        </summary>
                        <div class="absolute right-0 mt-3 w-72 overflow-hidden rounded-3xl border border-slate-100 bg-white p-2 shadow-2xl shadow-slate-200">
                            <div class="px-4 py-3">
                                <p class="text-sm font-black text-slate-950">{{ $trainerName }}</p>
                                <p class="text-xs font-semibold text-slate-500">Active trainer account</p>
                            </div>
                            <a href="{{ route('trainer.dashboard') }}" class="block rounded-2xl px-4 py-3 text-sm font-black text-slate-700 hover:bg-purple-50 hover:text-purple-700">Dashboard</a>
                            @if (auth()->user()?->role === 'trainer')
                                <form method="POST" action="{{ route('trainer.logout') }}" class="mt-2 border-t border-slate-100 pt-2">
                                    @csrf
                                    <button type="submit" class="w-full rounded-2xl px-4 py-3 text-left text-sm font-black text-red-600 hover:bg-red-50">Sign out</button>
                                </form>
                            @endif
                        </div>
                    </details>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-[1500px] px-5 py-8 lg:px-8 lg:py-10">
            @if (session('saved'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold leading-6 text-emerald-700">
                    {{ session('saved') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
