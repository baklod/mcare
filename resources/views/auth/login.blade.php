<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-slate-950 text-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Access | MCARE Training Center</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .login-glow-orb {
            background: radial-gradient(circle 500px at 50% 50%, rgba(147, 51, 234, 0.25), transparent 70%);
        }
        .login-glass-card {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="min-h-screen bg-slate-950 font-sans antialiased selection:bg-purple-500 selection:text-white">
    <div class="relative min-h-screen overflow-hidden grid grid-cols-1 lg:grid-cols-12">
        
        <!-- Left Showcase Side (Desktop 7 Cols) -->
        <section class="relative hidden lg:flex lg:col-span-7 flex-col justify-between p-12 lg:p-16 bg-gradient-to-br from-slate-950 via-purple-950 to-slate-900 text-white overflow-hidden">
            <div class="login-glow-orb absolute inset-0 pointer-events-none"></div>

            <!-- Top Header Logo -->
            <div class="relative z-10">
                <a href="{{ route('landing') }}" class="inline-flex items-center gap-3.5 group">
                    <img src="{{ asset('assets/official-logo.png') }}" alt="MCARE Logo" class="h-12 w-12 rounded-xl object-contain bg-white/10 p-1.5 ring-1 ring-white/20 transition-transform group-hover:scale-105">
                    <div>
                        <span class="block font-display text-xl font-black tracking-tight text-white">Mission Care</span>
                        <span class="block text-xs font-bold tracking-wider uppercase text-purple-300">Training & Assessment Center</span>
                    </div>
                </a>
            </div>

            <!-- Central Hero Copy & Role Grid -->
            <div class="relative z-10 max-w-xl my-auto py-12">
                <span class="inline-flex items-center gap-2 rounded-full border border-purple-400/30 bg-purple-500/10 px-3.5 py-1 text-xs font-semibold text-purple-300 backdrop-blur-md">
                    <span class="h-2 w-2 rounded-full bg-purple-400 animate-pulse"></span>
                    Unified Role Gateway
                </span>
                
                <h1 class="mt-6 font-display text-4xl xl:text-5xl font-black leading-tight text-white tracking-tight">
                    One single sign-in. <br>
                    <span class="bg-gradient-to-r from-purple-300 via-purple-200 to-white bg-clip-text text-transparent">Your exact workspace.</span>
                </h1>
                
                <p class="mt-5 text-base leading-relaxed text-slate-300">
                    Mission Care automatically detects your account profile and directs you directly to your approved portal.
                </p>

                <!-- 4 Interactive Role Indicators -->
                <div class="mt-10 grid grid-cols-2 gap-3.5">
                    <div class="login-glass-card rounded-2xl p-4 transition-all hover:border-purple-400/40">
                        <div class="flex items-center gap-2.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-300">Applicant</span>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-300">Enrollment & payment status</p>
                    </div>

                    <div class="login-glass-card rounded-2xl p-4 transition-all hover:border-purple-400/40">
                        <div class="flex items-center gap-2.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-300">Trainee</span>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-300">Class stream & quizzes</p>
                    </div>

                    <div class="login-glass-card rounded-2xl p-4 transition-all hover:border-purple-400/40">
                        <div class="flex items-center gap-2.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-sky-400"></span>
                            <span class="text-xs font-bold uppercase tracking-wider text-sky-300">Trainer</span>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-300">Materials & gradebook</p>
                    </div>

                    <div class="login-glass-card rounded-2xl p-4 transition-all hover:border-purple-400/40">
                        <div class="flex items-center gap-2.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-purple-400"></span>
                            <span class="text-xs font-bold uppercase tracking-wider text-purple-300">Admin</span>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-300">Batch ops & compliance</p>
                    </div>
                </div>
            </div>

            <!-- Footer Compliance Note -->
            <div class="relative z-10 flex items-center gap-6 text-xs text-slate-400">
                <span class="inline-flex items-center gap-1.5">
                    <x-dashboard-icon name="shield-check" class="text-purple-400 text-sm" />
                    TESDA NC II Standards
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <x-dashboard-icon name="lock" class="text-purple-400 text-sm" />
                    PayMongo Encrypted Gateway
                </span>
            </div>
        </section>

        <!-- Right Authentication Form Side (Desktop 5 Cols) -->
        <main class="col-span-1 lg:col-span-5 flex items-center justify-center p-6 sm:p-10 lg:p-12 bg-slate-900/50 backdrop-blur-xl">
            <div class="w-full max-w-md space-y-8">

                <!-- Mobile Top Logo (Visible on mobile viewports) -->
                <div class="lg:hidden text-center">
                    <a href="{{ route('landing') }}" class="inline-flex items-center gap-3">
                        <img src="{{ asset('assets/official-logo.png') }}" alt="MCARE Logo" class="h-12 w-12 rounded-xl object-contain bg-white/10 p-1.5 ring-1 ring-white/20">
                        <div class="text-left">
                            <span class="block font-display text-lg font-black text-white">Mission Care</span>
                            <span class="block text-xs font-bold tracking-wider uppercase text-purple-400">Account Access</span>
                        </div>
                    </a>
                </div>

                <!-- Form Card -->
                <div class="rounded-3xl border border-slate-800 bg-slate-900/90 p-7 sm:p-9 shadow-2xl shadow-purple-950/20">
                    
                    @include('auth.partials.current-account', ['activeUser' => $activeUser])

                    <div class="space-y-1">
                        <p class="text-xs font-bold uppercase tracking-widest text-purple-400">Account Portal</p>
                        <h2 class="font-display text-2xl sm:text-3xl font-black text-white">Sign in to MCARE</h2>
                        <p class="text-xs sm:text-sm text-slate-400">Enter your credentials or use Google 1-click sign-in.</p>
                    </div>

                    @if ($errors->any())
                        <div class="mt-6 rounded-2xl border border-red-500/30 bg-red-500/10 p-4 text-xs sm:text-sm font-semibold leading-relaxed text-red-300">
                            Please verify your account email and password.
                        </div>
                    @endif

                    <div class="mt-6 space-y-5">
                        
                        <!-- Primary Google OAuth Button -->
                        <a href="{{ route('auth.google.redirect') }}" class="group relative flex w-full items-center justify-center gap-3 rounded-2xl border border-slate-700 bg-slate-800/80 px-4 py-3.5 text-sm font-bold text-white shadow-sm transition-all hover:border-purple-500 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500/50">
                            <svg class="h-5 w-5 shrink-0 transition-transform group-hover:scale-110" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                            </svg>
                            <span>Continue with Google</span>
                        </a>

                        <div class="relative flex items-center justify-center text-center">
                            <hr class="w-full border-slate-800">
                            <span class="absolute bg-slate-900 px-3 text-[11px] font-bold uppercase tracking-wider text-slate-500">or email sign in</span>
                        </div>

                        <!-- Credentials Form -->
                        <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-300">Email Address</label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus placeholder="name@domain.com" class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm font-medium text-white placeholder-slate-500 transition-colors focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/20">
                                @error('email') <p class="mt-1.5 text-xs font-bold text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="password" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-300">Password</label>
                                <div class="relative">
                                    <input id="password" name="password" type="password" required placeholder="••••••••" class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-3 pr-10 text-sm font-medium text-white placeholder-slate-500 transition-colors focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/20">
                                    <button type="button" onclick="const p = document.getElementById('password'); p.type = p.type === 'password' ? 'text' : 'password';" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white" aria-label="Toggle password visibility">
                                        <x-dashboard-icon name="eye" class="text-sm" />
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-1">
                                <label class="flex items-center gap-2.5 text-xs font-semibold text-slate-400 cursor-pointer">
                                    <input name="remember" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-purple-600 focus:ring-purple-500/30 focus:ring-offset-slate-900">
                                    Remember this device
                                </label>
                            </div>

                            <button type="submit" class="w-full rounded-xl bg-purple-600 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-purple-600/30 transition-all hover:bg-purple-500 active:scale-[0.99]">
                                Sign In
                            </button>
                        </form>

                        <div class="pt-2 border-t border-slate-800/80 text-center">
                            <p class="text-xs font-medium text-slate-400">
                                New prospective student?
                                <a href="{{ route('enrollment.create') }}" class="font-bold text-purple-400 hover:text-purple-300 hover:underline">
                                    Start Enrollment Application &rarr;
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                <p class="text-center text-xs text-slate-500">
                    &copy; {{ date('Y') }} Mission Care Training and Assessment Center Inc.
                </p>
            </div>
        </main>
    </div>
</body>
</html>
