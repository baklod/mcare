<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainee Login | MCARE</title>
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
                    <span class="block text-sm font-bold uppercase text-purple-600">Trainee Portal</span>
                </span>
            </a>
            <h1 class="mt-10 max-w-2xl font-display text-5xl font-black leading-tight text-slate-900">Continue your Caregiving NC II journey.</h1>
            <p class="mt-5 max-w-xl text-base leading-8 text-slate-600">
                Access approved modules, batch schedules, payment status, and submitted TESDA registration documents.
            </p>
        </section>

        <section class="dashboard-panel p-7 shadow-2xl shadow-purple-100/50 sm:p-8">
            @include('auth.partials.current-account', ['activeUser' => $activeUser ?? auth()->user()])

            <p class="dashboard-section-kicker">Approved trainee access</p>
            <h2 class="mt-2 font-display text-3xl font-black text-slate-900">Trainee sign in</h2>

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold leading-6 text-red-700">
                    Please check your trainee account credentials.
                </div>
            @endif

            <form method="POST" action="{{ route('trainee.login.store') }}" class="mt-6 space-y-5">
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
                    Sign in as trainee
                </button>
            </form>
        </section>
    </main>
</body>
</html>
