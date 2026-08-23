<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f7f7fb]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | MCARE Training Center</title>
    <x-dashboard-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7f7fb] font-sans text-slate-900 antialiased selection:bg-purple-600 selection:text-white">
    <div class="flex min-h-screen flex-col justify-between py-8 px-4 sm:px-6 lg:px-8">
        
        <!-- Header Brand Link -->
        <header class="mx-auto w-full max-w-md pt-4 text-center">
            <a href="{{ route('landing') }}" class="inline-flex items-center gap-3 group">
                <img src="{{ asset('assets/official-logo.png') }}" alt="MCARE Logo" class="h-11 w-11 rounded-xl border border-slate-100 bg-white object-contain p-1 shadow-sm transition-transform group-hover:scale-105">
                <div class="text-left">
                    <span class="block font-display text-lg font-bold text-slate-900">Mission Care</span>
                    <span class="block text-xs font-medium text-slate-500">Training & Assessment Center</span>
                </div>
            </a>
        </header>

        <!-- Main Form Container Card -->
        <main class="mx-auto my-auto w-full max-w-md">
            <div class="rounded-2xl border border-slate-200 bg-white p-7 sm:p-9 shadow-sm">
                
                @include('auth.partials.current-account', ['activeUser' => $activeUser])

                <div class="mb-6">
                    <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900">{{ $mfaPending ? 'Verify your sign-in' : 'Sign in to your account' }}</h1>
                    <p class="mt-1.5 text-sm text-slate-500">
                        {{ $mfaPending ? 'Enter the six-digit code sent to your staff email address.' : 'One sign-in page for applicants, trainees, trainers, alumni, and administrators.' }}
                    </p>
                </div>

                @if (session('mfa_notice'))
                    <div class="mb-5 rounded-xl border border-purple-200 bg-purple-50 p-3.5 text-sm font-medium text-purple-800" role="status">
                        {{ session('mfa_notice') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-3.5 text-sm font-medium text-red-700">
                        {{ $mfaPending ? 'Please check the verification code and try again.' : 'Please check your account credentials and try again.' }}
                    </div>
                @endif

                <div class="space-y-4">
                    @if ($mfaPending)
                        <form method="POST" action="{{ route('login.verify-2fa') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label for="code" class="mb-1 block text-xs font-semibold text-slate-700">Verification code</label>
                                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus placeholder="000000" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-center text-2xl font-bold tracking-[0.4em] text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                                @error('code') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <button type="submit" class="w-full rounded-xl bg-purple-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-800 active:bg-purple-900">
                                Verify and continue
                            </button>
                        </form>

                        <div class="pt-3 border-t border-slate-100 text-center">
                            <a href="{{ route('login', ['cancel_mfa' => 1]) }}" class="text-xs font-semibold text-purple-700 hover:text-purple-800 hover:underline">
                                Use a different account
                            </a>
                        </div>
                    @else
                    <!-- Google OAuth Sign In Button -->
                    <a href="{{ route('auth.google.redirect') }}" class="flex w-full items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-colors hover:bg-slate-50 hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-1">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                        </svg>
                        <span>Continue with Google</span>
                    </a>

                    <div class="relative flex items-center justify-center py-1">
                        <hr class="w-full border-slate-200">
                        <span class="absolute bg-white px-3 text-xs font-medium text-slate-400">or sign in with email</span>
                    </div>

                    <!-- Standard Email & Password Form -->
                    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="email" class="mb-1 block text-xs font-semibold text-slate-700">Email address</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus placeholder="name@example.com" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            @error('email') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="password" class="mb-1 block text-xs font-semibold text-slate-700">Password</label>
                            <div class="relative">
                                <input id="password" name="password" type="password" required placeholder="••••••••" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 pr-10 text-sm text-slate-900 placeholder-slate-400 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                                <button type="button" onclick="const p = document.getElementById('password'); p.type = p.type === 'password' ? 'text' : 'password';" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" aria-label="Toggle password visibility">
                                    <x-dashboard-icon name="eye" class="text-sm" />
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <label class="flex items-center gap-2 text-xs font-medium text-slate-600 cursor-pointer">
                                <input name="remember" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                                Remember this device
                            </label>
                        </div>

                        <button type="submit" class="w-full rounded-xl bg-purple-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-800 active:bg-purple-900">
                            Sign In
                        </button>
                    </form>

                    <div class="pt-3 border-t border-slate-100 text-center">
                        <p class="text-xs text-slate-500">
                            New applicant?
                            <a href="{{ route('enrollment.create') }}" class="font-semibold text-purple-700 hover:text-purple-800 hover:underline">
                                Start your enrollment application
                            </a>
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="mx-auto w-full max-w-md pb-4 text-center text-xs text-slate-400">
            &copy; {{ date('Y') }} Mission Care Training and Assessment Center Inc.
        </footer>
    </div>
</body>
</html>
