<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Login | MCARE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-80 bg-gradient-to-b from-purple-100 via-purple-50/70 to-slate-50"></div>

    <main class="mx-auto grid min-h-screen max-w-6xl grid-cols-1 items-center gap-10 px-6 py-12 lg:grid-cols-[1fr_420px] lg:px-8">
        <section>
            <a href="{{ route('landing') }}" class="inline-flex items-center gap-4">
                <img src="{{ asset('assets/official-logo.png') }}" alt="Mission Care logo" class="h-16 w-16 rounded-2xl object-contain">
                <span>
                    <span class="block text-2xl font-black text-slate-900">Mission Care</span>
                    <span class="block text-sm font-bold uppercase text-purple-600">Trainer Portal</span>
                </span>
            </a>
            <h1 class="mt-10 max-w-2xl text-5xl font-black leading-tight text-slate-900">Instructor workspace for Caregiving NC II training.</h1>
            <p class="mt-5 max-w-xl text-base leading-8 text-slate-600">
                Upload learning materials, monitor AM/PM trainees, and keep batch schedules aligned with MCARE admin operations.
            </p>
        </section>

        <section class="rounded-3xl border border-purple-100 bg-white p-7 shadow-2xl shadow-purple-100/50 sm:p-8">
            @include('auth.partials.current-account', ['activeUser' => $activeUser ?? auth()->user()])

            <p class="text-sm font-bold uppercase text-purple-600">Secure access</p>
            <h2 class="mt-2 text-3xl font-black text-slate-900">Trainer sign in</h2>

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold leading-6 text-red-700">
                    Please check your trainer account credentials.
                </div>
            @endif

            <form method="POST" action="{{ route('trainer.login.store') }}" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-slate-800">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                    @error('email') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-800">Password</label>
                    <input id="password" name="password" type="password" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                </div>

                <label class="flex items-center gap-3 text-sm font-semibold text-slate-600">
                    <input name="remember" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                    Remember this device
                </label>

                <button type="submit" class="inline-flex w-full items-center justify-center rounded-full bg-purple-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">
                    Sign in as trainer
                </button>
            </form>
        </section>
    </main>
</body>
</html>
