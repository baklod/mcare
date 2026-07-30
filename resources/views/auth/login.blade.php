<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Login | MCARE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased selection:bg-purple-600 selection:text-white">
    <div class="min-h-screen flex flex-col justify-between">
        
        <!-- Top Navigation Header -->
        <header class="w-full border-b border-slate-200/80 bg-white py-4 px-6 sm:px-8">
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <a href="{{ route('landing') }}" class="inline-flex items-center gap-3.5 group">
                    <img src="{{ asset('assets/official-logo.png') }}" alt="Mission Care logo" class="h-10 w-10 rounded-xl object-contain border border-slate-100 bg-white p-1">
                    <span>
                        <span class="block font-display text-lg font-black text-slate-900">Mission Care</span>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-purple-700">Account Access</span>
                    </span>
                </a>
                <a href="{{ route('landing') }}" class="text-xs font-bold text-slate-600 hover:text-purple-700 flex items-center gap-1.5">
                    <x-dashboard-icon name="arrow-left" class="text-xs" />
                    Back to Homepage
                </a>
            </div>
        </header>

        <!-- Main Content Grid -->
        <main class="mx-auto my-auto w-full max-w-6xl px-6 py-10 lg:py-16">
            <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12">
                
                <!-- Left Information Column -->
                <section class="lg:col-span-7 space-y-8">
                    <div>
                        <span class="inline-flex items-center gap-2 rounded-full border border-purple-200 bg-purple-50 px-3.5 py-1 text-xs font-bold text-purple-700">
                            <span class="h-2 w-2 rounded-full bg-purple-600"></span>
                            Unified Role Gateway
                        </span>
                        <h1 class="mt-4 font-display text-4xl font-black leading-tight text-slate-900 sm:text-5xl">
                            One login, correct MCARE workspace.
                        </h1>
                        <p class="mt-4 max-w-xl text-base leading-relaxed text-slate-600">
                            Applicants can continue enrollment and payment, while approved trainees, trainers, and admins are automatically routed directly to their proper dashboards.
                        </p>
                    </div>

                    <!-- 4 Role Workspace Badges (Solid White Cards) -->
                    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 max-w-xl">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4.5 shadow-sm">
                            <p class="text-xs font-black uppercase tracking-wider text-amber-700">1. Applicant</p>
                            <p class="mt-1 text-xs font-medium text-slate-600">Enrollment & payment status</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4.5 shadow-sm">
                            <p class="text-xs font-black uppercase tracking-wider text-emerald-700">2. Trainee</p>
                            <p class="mt-1 text-xs font-medium text-slate-600">Class stream & interactive quizzes</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4.5 shadow-sm">
                            <p class="text-xs font-black uppercase tracking-wider text-sky-700">3. Trainer</p>
                            <p class="mt-1 text-xs font-medium text-slate-600">Classwork & quiz authoring</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4.5 shadow-sm">
                            <p class="text-xs font-black uppercase tracking-wider text-purple-700">4. Admin</p>
                            <p class="mt-1 text-xs font-medium text-slate-600">Batch operations & 2FA security</p>
                        </div>
                    </div>
                </section>

                <!-- Right Sign-In Form Card (Solid White Elevated) -->
                <section class="lg:col-span-5">
                    <div class="rounded-3xl border border-slate-200/90 bg-white p-7 sm:p-9 shadow-xl shadow-slate-200/60">
                        
                        @include('auth.partials.current-account', ['activeUser' => $activeUser])

                        <div class="mb-6 space-y-1">
                            <p class="text-xs font-black uppercase tracking-wider text-purple-700">Secure Sign In</p>
                            <h2 class="font-display text-2xl font-black text-slate-900">Sign in to your account</h2>
                        </div>

                        @if ($errors->any())
                            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700 leading-relaxed">
                                Please check your email and password credentials.
                            </div>
                        @endif

                        <div class="space-y-5">
                            
                            <!-- Primary Google OAuth Button -->
                            <a href="{{ route('auth.google.redirect') }}" class="flex w-full items-center justify-center gap-3 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition-all hover:bg-slate-50 hover:border-slate-400">
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.62z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                                </svg>
                                <span>Continue with Google</span>
                            </a>

                            <div class="relative flex items-center justify-center text-center">
                                <hr class="w-full border-slate-200">
                                <span class="absolute bg-white px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">or use email credentials</span>
                            </div>

                            <!-- Credentials Form -->
                            <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Email Address</label>
                                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus placeholder="your.name@example.com" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-900 placeholder-slate-400 transition-colors focus:border-purple-600 focus:outline-none focus:ring-2 focus:ring-purple-600/20">
                                    @error('email') <p class="mt-1.5 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="password" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Password</label>
                                    <div class="relative">
                                        <input id="password" name="password" type="password" required placeholder="••••••••" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 pr-10 text-sm font-medium text-slate-900 placeholder-slate-400 transition-colors focus:border-purple-600 focus:outline-none focus:ring-2 focus:ring-purple-600/20">
                                        <button type="button" onclick="const p = document.getElementById('password'); p.type = p.type === 'password' ? 'text' : 'password';" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700" aria-label="Toggle password visibility">
                                            <x-dashboard-icon name="eye" class="text-sm" />
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-1">
                                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer">
                                        <input name="remember" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-600">
                                        Remember this device
                                    </label>
                                </div>

                                <button type="submit" class="w-full rounded-xl bg-purple-700 px-5 py-3.5 text-sm font-bold text-white shadow-md shadow-purple-700/20 transition-all hover:bg-purple-800">
                                    Sign In
                                </button>
                            </form>

                            <div class="pt-3 border-t border-slate-100 text-center">
                                <a href="{{ route('enrollment.create') }}" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs font-bold text-slate-700 hover:bg-slate-100">
                                    New applicant enrollment &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full border-t border-slate-200/80 bg-white py-4 px-6 text-center text-xs text-slate-500">
            &copy; {{ date('Y') }} Mission Care Training and Assessment Center Inc. All rights reserved.
        </footer>
    </div>
</body>
</html>
