<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | MCARE</title>
    <x-dashboard-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="account-page min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="account-header border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
            <a href="{{ $portalUrl }}" class="flex min-w-0 items-center gap-3">
                <img src="{{ asset('assets/mcare-mark.png') }}" alt="MCARE mark" class="h-11 w-11 rounded-xl border border-slate-100 object-contain p-1">
                <span><strong class="block text-sm sm:text-base">MCARE Hub</strong><span class="text-xs font-semibold text-purple-700">{{ $roleLabel }} settings</span></span>
            </a>
            <a href="{{ $portalUrl }}" class="secondary-action"><x-dashboard-icon name="arrow-left" class="mr-2" />Back to dashboard</a>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-5 py-8 sm:px-8 sm:py-12">
        @if (session('saved'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">{{ session('saved') }}</div>
        @endif
        <div class="mb-8">
            <p class="text-sm font-bold uppercase tracking-wide text-purple-700">Account preferences</p>
            <h1 class="mt-2 text-3xl font-black sm:text-4xl">Settings</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">Manage the signed-in {{ strtolower($roleLabel) }} account and display preference.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_1.4fr]">
            <div class="space-y-6">
                <section class="account-card rounded-2xl border border-slate-200 bg-white p-6">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Signed-in account</p>
                    <h2 class="mt-3 text-xl font-black">{{ $user->name }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $user->email }}</p>
                    <span class="mt-4 inline-flex rounded-lg bg-purple-50 px-3 py-1.5 text-xs font-bold text-purple-800">{{ $roleLabel }}</span>
                </section>
                <section id="preferences" class="account-card rounded-2xl border border-slate-200 bg-white p-6">
                    <p class="text-xs font-bold uppercase tracking-wide text-purple-700">Display</p>
                    <h2 class="mt-2 text-xl font-black">Theme preference</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Night mode is stored only on this browser and can be changed anytime.</p>
                    <button type="button" class="secondary-action mt-5 w-full" data-dashboard-theme-toggle aria-pressed="false">
                        <x-dashboard-icon name="moon" class="mr-2" data-dashboard-theme-icon="moon" />
                        <x-dashboard-icon name="sun" class="mr-2 hidden" data-dashboard-theme-icon="sun" />
                        <span data-dashboard-theme-label>Night mode</span>
                    </button>
                </section>
                <a href="{{ route('account.help') }}" class="account-card block rounded-2xl border border-slate-200 bg-white p-6 transition hover:border-purple-300">
                    <p class="text-xs font-bold uppercase tracking-wide text-purple-700">Need assistance?</p>
                    <h2 class="mt-2 text-xl font-black">Open help center</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">View guidance tailored to the {{ strtolower($roleLabel) }} portal.</p>
                </a>
            </div>

            <section id="change-password" class="account-card scroll-mt-8 rounded-2xl border border-slate-200 bg-white p-6 sm:p-8">
                <p class="text-xs font-bold uppercase tracking-wide text-purple-700">Security</p>
                <h2 class="mt-2 text-2xl font-black">Change password</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Use at least eight characters with both letters and numbers.</p>
                <form method="POST" action="{{ route('account.password.update') }}" class="mt-6 space-y-5">
                    @csrf
                    @method('PATCH')
                    <label class="block text-sm font-bold text-slate-700">Current password
                        <input name="current_password" type="password" autocomplete="current-password" required class="form-field mt-2 text-base">
                        @error('current_password') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm font-bold text-slate-700">New password
                        <input name="password" type="password" autocomplete="new-password" required class="form-field mt-2 text-base">
                        @error('password') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm font-bold text-slate-700">Confirm new password
                        <input name="password_confirmation" type="password" autocomplete="new-password" required class="form-field mt-2 text-base">
                    </label>
                    <button type="submit" class="primary-action w-full sm:w-auto"><x-dashboard-icon name="key" class="mr-2" />Update password</button>
                </form>
            </section>
        </div>
    </main>
</body>
</html>
