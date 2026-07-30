<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Login | MCARE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dashboard-shell">
    <div class="dashboard-gradient"></div>

    <main class="mx-auto grid min-h-screen max-w-6xl grid-cols-1 items-center gap-10 px-6 py-12 lg:grid-cols-[1fr_420px] lg:px-8">
        <section>
            <a href="{{ route('landing') }}" class="inline-flex items-center gap-4">
                <img src="{{ asset('assets/official-logo.png') }}" alt="Mission Care logo" class="h-16 w-16 rounded-2xl object-contain">
                <span>
                    <span class="block font-display text-2xl font-black text-slate-900">Mission Care</span>
                    <span class="block text-sm font-bold uppercase text-purple-600">Account Access</span>
                </span>
            </a>
            <h1 class="mt-10 max-w-2xl font-display text-5xl font-black leading-tight text-slate-900">One login, correct MCARE workspace.</h1>
            <p class="mt-5 max-w-xl text-base leading-8 text-slate-600">
                Applicants can continue enrollment/payment, while approved trainees, trainers, and admins are routed to their proper dashboards.
            </p>
            <div class="mt-8 grid grid-cols-2 gap-3 sm:max-w-xl sm:grid-cols-4">
                @foreach (['Applicant', 'Trainee', 'Trainer', 'Admin'] as $role)
                    <div class="rounded-2xl border border-purple-100 bg-white p-4 text-center shadow-sm">
                        <p class="text-xs font-black uppercase tracking-wide text-purple-600">{{ $role }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="dashboard-panel p-7 shadow-2xl shadow-purple-100/50 sm:p-8">
            @include('auth.partials.current-account', ['activeUser' => $activeUser])

            <p class="dashboard-section-kicker">Secure account</p>
            <h2 class="mt-2 font-display text-3xl font-black text-slate-900">Sign in</h2>

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold leading-6 text-red-700">
                    Please check your account credentials.
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-slate-800">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="form-field">
                    @error('email') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-800">Password</label>
                    <input id="password" name="password" type="password" required class="form-field">
                </div>

                <label class="flex items-center gap-3 text-sm font-semibold text-slate-600">
                    <input name="remember" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                    Remember this device
                </label>

                <button type="submit" class="primary-action w-full">
                    Sign in
                </button>

                <div class="relative my-6 text-center text-xs font-bold uppercase tracking-wider text-slate-400">
                    <span class="bg-white px-3 relative z-10">or continue with</span>
                    <hr class="absolute inset-0 top-1/2 -z-0 border-slate-200">
                </div>

                <a href="{{ route('auth.google.redirect') }}" class="secondary-action w-full flex items-center justify-center gap-3 border-slate-200 bg-white text-slate-700 hover:bg-slate-50">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span>Continue with Google</span>
                </a>

                <a href="{{ route('enrollment.create') }}" class="secondary-action w-full text-center">
                    New applicant enrollment
                </a>
            </form>
        </section>
    </main>
</body>
</html>
