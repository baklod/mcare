<!DOCTYPE html>
<html lang="en" class="bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $status }} | MCARE</title>
    <x-site-favicon />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-slate-900 antialiased">
    <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-80 bg-gradient-to-b from-purple-100 via-purple-50/70 to-white"></div>

    <main class="mx-auto flex min-h-screen max-w-3xl flex-col items-center justify-center px-6 py-16 text-center">
        <a href="{{ route('landing') }}" class="mb-8 inline-flex items-center gap-3">
            <img src="{{ asset('assets/official-logo.png') }}" alt="Mission Care Training Center logo" class="h-14 w-14 rounded-2xl object-contain">
            <span class="mcare-brand text-left">
                <span class="mcare-mark">MCARE</span>
                <p class="mcare-brand-name">Mission Care</p>
            </span>
        </a>

        <div class="rounded-full bg-purple-50 px-5 py-2 text-sm font-bold text-purple-700 ring-1 ring-purple-100">
            Error {{ $status }}
        </div>
        <h1 class="mt-7 text-4xl font-bold leading-tight text-slate-900 sm:text-5xl">{{ $title }}</h1>
        <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600">{{ $message }}</p>

        <div class="mt-9 flex flex-col gap-3 sm:flex-row">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('landing') }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm hover:border-purple-200 hover:text-purple-700">
                Go back
            </a>
            <a href="{{ route('landing') }}" class="inline-flex items-center justify-center rounded-full bg-purple-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">
                Return home
            </a>
        </div>
    </main>
</body>
</html>
