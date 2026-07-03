<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'MCARE Admin' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-72 bg-gradient-to-b from-purple-100 via-purple-50/70 to-slate-50"></div>

    <header class="border-b border-purple-100 bg-white/95 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between lg:px-8">
            <a href="{{ route('admin.enrollments.index') }}" class="flex items-center gap-4">
                <img src="{{ asset('assets/official-logo.png') }}" alt="Mission Care Training Center logo" class="h-14 w-14 rounded-2xl object-contain">
                <span>
                    <span class="block text-base font-bold text-slate-900">MCARE Admin</span>
                    <span class="block text-sm text-slate-500">Enrollment review workspace</span>
                </span>
            </a>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('landing') }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:border-purple-200 hover:text-purple-700">
                    Landing
                </a>

                @auth
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center rounded-full bg-purple-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">
                            Sign out
                        </button>
                    </form>
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-6 py-8 lg:px-8 lg:py-10">
        @if (session('saved'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold leading-6 text-emerald-700">
                {{ session('saved') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
