<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $roleLabel }} Help | MCARE</title>
    <x-dashboard-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="account-page min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="account-header border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
            <a href="{{ $portalUrl }}" class="flex items-center gap-3">
                <img src="{{ asset('assets/mcare-mark.png') }}" alt="MCARE mark" class="h-11 w-11 rounded-xl border border-slate-100 object-contain p-1">
                <span><strong class="block">MCARE Help Center</strong><span class="text-xs font-semibold text-purple-700">{{ $roleLabel }} guide</span></span>
            </a>
            <a href="{{ $portalUrl }}" class="secondary-action"><x-dashboard-icon name="arrow-left" class="mr-2" />Back</a>
        </div>
    </header>
    <main class="mx-auto max-w-5xl px-5 py-10 sm:px-8">
        <p class="text-sm font-bold uppercase tracking-wide text-purple-700">Role-aware assistance</p>
        <h1 class="mt-2 text-3xl font-black sm:text-4xl">Help for {{ $roleLabel }}</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Quick guidance for the actions available in your current portal.</p>
        <div class="mt-8 grid gap-5 md:grid-cols-3">
            @foreach ($topics as [$title, $description])
                <section class="account-card rounded-2xl border border-slate-200 bg-white p-6">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-purple-50 text-purple-700"><x-dashboard-icon name="circle-check" /></span>
                    <h2 class="mt-4 text-lg font-black">{{ $title }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p>
                </section>
            @endforeach
        </div>
        <section class="account-card mt-6 rounded-2xl border border-purple-200 bg-white p-6 sm:flex sm:items-center sm:justify-between sm:gap-6">
            <div><h2 class="text-xl font-black">Still need help?</h2><p class="mt-2 text-sm text-slate-600">Contact Mission Care Training Center and include your account email: {{ $user->email }}</p></div>
            <a href="{{ route('account.settings') }}" class="secondary-action mt-5 sm:mt-0">Account settings</a>
        </section>
    </main>
</body>
</html>
