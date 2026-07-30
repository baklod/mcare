<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCARE | Mission Care Training and Assessment Center</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Path: resources/views/landing/home.blade.php | Label: Main landing background */
        .landing-grid-bg {
            background-image:
                linear-gradient(to right, #f0f0f0 1px, transparent 1px),
                linear-gradient(to bottom, #f0f0f0 1px, transparent 1px),
                radial-gradient(circle 600px at 0% 200px, #d5c5ff, transparent),
                radial-gradient(circle 600px at 100% 200px, #d5c5ff, transparent);
            background-size: 20px 20px, 20px 20px, 100% 100%, 100% 100%;
        }

        /* Path: resources/views/landing/home.blade.php | Label: Solid sticky header */
        .site-header {
            transition: border-color 180ms ease, box-shadow 180ms ease;
        }

        .site-header.is-scrolled {
            background: #ffffff;
            border-color: #e2e8f0;
            box-shadow: 0 1px 0 rgba(15, 23, 42, 0.04);
        }

        /* Path: resources/views/landing/home.blade.php | Label: 6.7-inch phone header layout */
        @media (max-width: 767px) {
            .site-header {
                background: #ffffff;
            }

            /* Path: resources/views/landing/home.blade.php | Label: Phone-first landing density */
            .phone-hero-copy {
                max-width: 24rem;
                margin-inline: auto;
                text-align: center;
            }

            .phone-section-heading {
                max-width: 24rem;
            }

            .phone-hero-card {
                max-width: 20.5rem;
            }

            .phone-hero-frame {
                border-radius: 1.5rem;
                padding: 0.625rem;
            }

            .phone-hero-media {
                border-radius: 1.125rem;
            }

            .phone-soft-card {
                border-radius: 1rem;
                box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
            }
        }

        /* Path: resources/views/landing/home.blade.php | Label: Mobile sidebar motion */
        .mobile-sidebar-shell {
            opacity: 0;
            pointer-events: none;
            transition: opacity 180ms ease;
        }

        .mobile-sidebar-panel {
            transform: translateX(100%);
            transition: transform 220ms ease;
        }

        .mobile-sidebar-shell.is-open {
            opacity: 1;
            pointer-events: auto;
        }

        .mobile-sidebar-shell.is-open .mobile-sidebar-panel {
            transform: translateX(0);
        }

        /* Path: resources/views/landing/home.blade.php | Label: Hero image carousel */
        .hero-slide {
            opacity: 0;
            transform: translate3d(18px, 0, 0) scale(0.985);
            animation: hero-slide-cycle 15s infinite;
            will-change: opacity, transform;
        }

        .hero-slide:nth-child(2) {
            animation-delay: 5s;
        }

        .hero-slide:nth-child(3) {
            animation-delay: 10s;
        }

        .hero-dot {
            animation: hero-dot-cycle 15s infinite;
        }

        .hero-dot:nth-child(2) {
            animation-delay: 5s;
        }

        .hero-dot:nth-child(3) {
            animation-delay: 10s;
        }

        @keyframes hero-slide-cycle {
            0%, 30% {
                opacity: 1;
                transform: translate3d(0, 0, 0) scale(1);
            }

            36%, 100% {
                opacity: 0;
                transform: translate3d(-18px, 0, 0) scale(0.985);
            }
        }

        @keyframes hero-dot-cycle {
            0%, 30% {
                width: 1.75rem;
                background: rgb(147 51 234);
            }

            36%, 100% {
                width: 0.5rem;
                background: rgb(216 180 254);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .hero-slide,
            .hero-dot {
                animation: none;
            }

            .hero-slide:first-child {
                opacity: 1;
                transform: none;
            }
        }

        /* Path: resources/views/landing/home.blade.php | Label: Footer-only radial background */
        .footer-radial-bg {
            background: radial-gradient(125% 125% at 50% 10%, #fff 40%, #7c3aed 100%);
        }

        /* Path: resources/views/landing/home.blade.php | Label: Discover section bottom radial background */
        .discover-radial-bg {
            background: radial-gradient(125% 125% at 50% 90%, #fff 40%, #7c3aed 100%);
        }

        /* Path: resources/views/landing/home.blade.php | Label: Discover text fade animation */
        .discover-fade {
            opacity: 0;
            transform: translate3d(0, 14px, 0);
            transition: opacity 420ms ease, transform 420ms ease;
            will-change: opacity, transform;
        }

        .discover-fade.is-visible {
            opacity: 1;
            transform: translate3d(0, 0, 0);
        }

        @media (prefers-reduced-motion: reduce) {
            .discover-fade {
                opacity: 1;
                transform: none;
                transition: none;
                will-change: auto;
            }
        }
    </style>
</head>
<body class="relative overflow-x-hidden bg-white font-sans text-slate-900 antialiased">
    @php
        // Replace these placeholder URLs with MCARE's official social pages.
        $socialLinks = [
            'facebook' => 'https://www.facebook.com/',
            'instagram' => 'https://www.instagram.com/',
            'youtube' => 'https://www.youtube.com/',
        ];

        // Replace these with public Facebook video post URLs from MCARE's official page.
        $facebookVideos = [
            [
                'title' => 'Training highlights',
                'description' => 'Showcase caregiving training, classroom work, or skills demos.',
                'url' => 'https://www.facebook.com/facebook/videos/10153231379946729/',
            ],
            [
                'title' => 'Student activities',
                'description' => 'Feature enrollment updates, class activities, or assessment preparation.',
                'url' => 'https://www.facebook.com/facebook/videos/10153231379946729/',
            ],
            [
                'title' => 'MCARE updates',
                'description' => 'Share announcements, program reminders, and community posts.',
                'url' => 'https://www.facebook.com/facebook/videos/10153231379946729/',
            ],
        ];

        $currentUser = auth()->user();
        $accountCtaUrl = $currentUser ? \App\Support\AccountPortal::urlFor($currentUser) : route('enrollment.create');
        $accountCtaLabel = $currentUser ? \App\Support\AccountPortal::ctaLabelFor($currentUser) : 'Start Enrollment';
        $accountRoleLabel = \App\Support\AccountPortal::roleLabelFor($currentUser);
        $accountInitial = $currentUser ? \Illuminate\Support\Str::of($currentUser->name ?: $currentUser->email)->substr(0, 1)->upper() : null;
    @endphp

    <!-- Path: resources/views/landing/home.blade.php | Label: Main landing background layer -->
    <div class="landing-grid-bg pointer-events-none fixed inset-0 z-0"></div>

    <!-- Path: resources/views/landing/home.blade.php | Label: Clean single-row header -->
    <header id="site-header" class="site-header sticky top-0 z-50 border-b border-slate-200 bg-white">
        <nav class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-4 px-4 py-2 sm:min-h-20 sm:px-6 lg:px-8" aria-label="Main navigation">
            <a href="{{ route('landing') }}" class="flex min-w-0 items-center gap-3">
                <img src="{{ asset('assets/mcare-mark.png') }}" alt="MCARE mark" class="h-10 w-10 shrink-0 rounded-lg border border-slate-100 bg-white object-contain sm:h-12 sm:w-12">
                <span class="min-w-0">
                    <span class="block text-sm font-bold leading-5 text-slate-900">MCARE</span>
                    <span class="block max-w-[170px] truncate text-[11px] leading-4 text-slate-500 sm:max-w-none sm:text-xs">Mission Care Training Center</span>
                </span>
            </a>

            <div class="hidden items-center gap-6 lg:flex">
                <a href="#programs" class="text-sm font-semibold text-slate-600 hover:text-purple-700">Programs</a>
                <a href="#admissions" class="text-sm font-semibold text-slate-600 hover:text-purple-700">Admissions</a>
                <a href="#why" class="text-sm font-semibold text-slate-600 hover:text-purple-700">Why MCARE</a>
                <a href="#discover" class="text-sm font-semibold text-slate-600 hover:text-purple-700">Discover</a>
                <a href="#contact" class="text-sm font-semibold text-slate-600 hover:text-purple-700">Contact</a>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                @auth
                    <!-- Path: resources/views/landing/home.blade.php | Label: Compact active account menu -->
                    <details class="relative hidden md:block">
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-left hover:bg-slate-50">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-purple-100 text-sm font-bold text-purple-700">{{ $accountInitial }}</span>
                            <span class="hidden min-w-0 max-w-32 lg:block">
                                <span class="block truncate text-sm font-bold text-slate-900">{{ $currentUser->name }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ $accountRoleLabel }}</span>
                            </span>
                            <x-dashboard-icon name="chevron-down" class="mx-1 text-xs text-slate-400" />
                        </summary>
                        <div class="absolute right-0 mt-2 w-64 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                            <div class="border-b border-slate-100 px-3 py-2.5">
                                <p class="truncate text-sm font-bold text-slate-900">{{ $currentUser->name }}</p>
                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ $currentUser->email }}</p>
                            </div>
                            <a href="{{ $accountCtaUrl }}" class="mt-1 flex items-center justify-between rounded-lg px-3 py-2.5 text-sm font-semibold text-purple-700 hover:bg-purple-50">
                                <span>{{ $accountCtaLabel }}</span>
                                <x-dashboard-icon name="arrow-right" class="text-xs" />
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-slate-100 pt-1">
                                @csrf
                                <button type="submit" class="w-full rounded-lg px-3 py-2.5 text-left text-sm font-semibold text-red-600 hover:bg-red-50">Sign out</button>
                            </form>
                        </div>
                    </details>
                @else
                    <a href="{{ route('login') }}" class="inline-flex rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-purple-200 hover:text-purple-700">Sign in</a>
                    <a href="{{ route('enrollment.create') }}" class="inline-flex rounded-lg bg-purple-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-purple-800">Enroll now</a>
                @endauth

                <button id="mobile-menu-open" type="button" aria-controls="mobile-sidebar" aria-expanded="false" class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 hover:border-purple-200 hover:text-purple-700 lg:hidden">
                    <span class="sr-only">Open navigation menu</span>
                    <x-dashboard-icon name="bars" />
                </button>
            </div>
        </nav>
    </header>

    <!-- Path: resources/views/landing/home.blade.php | Label: Mobile sidebar navigation -->
    <div id="mobile-sidebar" class="mobile-sidebar-shell fixed inset-0 z-[60] md:hidden" aria-hidden="true">
        <button id="mobile-menu-overlay" type="button" class="absolute inset-0 bg-slate-950/35" aria-label="Close navigation menu"></button>
        <aside class="mobile-sidebar-panel absolute right-0 top-0 flex h-full w-[min(86vw,340px)] flex-col border-l border-purple-100 bg-white shadow-2xl shadow-purple-950/20">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-4">
                <a href="{{ route('landing') }}" class="flex items-center gap-3">
                    <img src="{{ asset('assets/mcare-mark.png') }}" alt="MCARE mark" class="h-10 w-10 rounded-lg border border-slate-100 object-contain">
                    <span>
                        <span class="block text-sm font-bold text-slate-900">MCARE</span>
                        <span class="block text-xs text-slate-500">Navigation</span>
                    </span>
                </a>
                <button id="mobile-menu-close" type="button" class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 hover:border-purple-200 hover:text-purple-700">
                    <span class="sr-only">Close navigation menu</span>
                    <x-dashboard-icon name="xmark" />
                </button>
            </div>

            <nav class="flex-1 space-y-1.5 overflow-y-auto px-4 py-4" aria-label="Mobile navigation">
                @auth
                    <!-- Path: resources/views/landing/home.blade.php | Label: Mobile active account identity -->
                    <div class="mb-3 rounded-2xl border border-purple-100 bg-purple-50/80 p-4">
                        <div class="flex items-start gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-purple-600 text-sm font-black text-white">
                                {{ $accountInitial }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-wide text-purple-700">Active account</p>
                                <p class="truncate text-sm font-bold text-slate-900" title="{{ $currentUser->name }}">{{ $currentUser->name }}</p>
                                <p class="mt-0.5 truncate text-xs font-semibold text-slate-500" title="{{ $currentUser->email }}">{{ $currentUser->email }}</p>
                                <p class="mt-1 inline-flex rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-purple-700">{{ $accountRoleLabel }}</p>
                            </div>
                        </div>
                    </div>
                @endauth
                <a href="#programs" class="mobile-sidebar-link block rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Programs</a>
                <a href="#admissions" class="mobile-sidebar-link block rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Admissions</a>
                <a href="#why" class="mobile-sidebar-link block rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Why MCARE</a>
                <a href="#discover" class="mobile-sidebar-link block rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Discover</a>
                <a href="#contact" class="mobile-sidebar-link block rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Contact</a>
            </nav>

            <div class="border-t border-slate-100 p-4">
                @auth
                    <a href="{{ $accountCtaUrl }}" class="inline-flex w-full items-center justify-center rounded-full bg-purple-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">{{ $accountCtaLabel }}</a>
                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:border-purple-200 hover:text-purple-700">
                            Sign out
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="mb-2.5 inline-flex w-full items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:border-purple-200 hover:text-purple-700">Sign in</a>
                    <a href="{{ route('enrollment.create') }}" class="inline-flex w-full items-center justify-center rounded-full bg-purple-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">Start Enrollment</a>
                @endauth
            </div>
        </aside>
    </div>

    <main class="relative z-10">
        <section class="relative overflow-hidden bg-white/45 backdrop-blur-[1px]">
            <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-8 px-4 py-8 sm:gap-10 sm:px-6 sm:py-20 lg:grid-cols-2 lg:gap-16 lg:px-8 lg:py-28">
                <div class="phone-hero-copy sm:text-left">
                    <h1 class="mt-0 max-w-3xl text-[2rem] font-bold leading-[2.3rem] text-slate-900 sm:text-6xl sm:leading-none">
                        Build a caregiving career with a training center you can trust.
                    </h1>

                    <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-600 sm:mt-7 sm:text-lg sm:leading-8">
                        MCARE connects Mission Care applicants to a clearer enrollment pathway, TESDA-oriented caregiving programs, class scheduling, learning records, and future alumni career support.
                    </p>

                    <div class="mt-6 grid grid-cols-1 gap-2.5 sm:mt-10 sm:flex sm:flex-row sm:gap-3">
                        @auth
                            <a href="{{ $accountCtaUrl }}" class="inline-flex h-11 items-center justify-center rounded-full bg-purple-600 px-6 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700 sm:h-auto sm:px-7 sm:py-3.5">{{ $accountCtaLabel }}</a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex h-11 items-center justify-center rounded-full border border-purple-200 bg-purple-50 px-6 text-sm font-bold text-purple-700 hover:bg-purple-100 sm:h-auto sm:px-7 sm:py-3.5">Sign In</a>
                            <a href="{{ route('enrollment.create') }}" class="inline-flex h-11 items-center justify-center rounded-full bg-purple-600 px-6 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700 sm:h-auto sm:px-7 sm:py-3.5">Start Enrollment</a>
                        @endauth
                        <a href="#programs" class="inline-flex h-11 items-center justify-center rounded-full border border-slate-200 bg-white px-6 text-sm font-semibold text-slate-700 hover:border-purple-200 hover:text-purple-700 sm:h-auto sm:px-7 sm:py-3.5">View Programs</a>
                    </div>

                    <div class="mt-7 grid max-w-xl grid-cols-3 gap-3 border-t border-slate-100 pt-5 text-left sm:mt-10 sm:gap-5 sm:pt-8">
                        <div>
                            <p class="text-lg font-bold text-slate-900 sm:text-2xl">NC II</p>
                            <p class="mt-1 text-[11px] leading-4 text-slate-500 sm:text-sm sm:leading-6">Caregiving pathway</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-slate-900 sm:text-2xl">OAuth</p>
                            <p class="mt-1 text-[11px] leading-4 text-slate-500 sm:text-sm sm:leading-6">Secure entry</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-slate-900 sm:text-2xl">Hub</p>
                            <p class="mt-1 text-[11px] leading-4 text-slate-500 sm:text-sm sm:leading-6">Training to alumni</p>
                        </div>
                    </div>

                    @if (session('google_config_missing'))
                        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-medium leading-6 text-amber-800">
                            {{ session('google_config_missing') }}
                        </div>
                    @endif

                    @if (session('auth_error'))
                        <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium leading-6 text-red-700">
                            {{ session('auth_error') }}
                        </div>
                    @endif

                    @if (session('signed_in'))
                        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium leading-6 text-emerald-700">
                            {{ session('signed_in') }}
                        </div>
                    @endif
                </div>

                <div class="phone-hero-card relative mx-auto w-full sm:max-w-[560px] lg:mx-0">
                    <div class="phone-hero-frame relative rounded-[2rem] border border-purple-100 bg-white/80 p-4 shadow-2xl shadow-purple-100/70 backdrop-blur">
                        <div class="phone-hero-media relative aspect-[4/5] overflow-hidden rounded-[1.5rem] bg-slate-100">
                            <div class="hero-slide absolute inset-0">
                                <img src="{{ asset('assets/landing/caregiving-training-hero.png') }}" alt="MCARE caregiving training preview" class="h-full w-full object-cover">
                                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/75 via-slate-950/20 to-transparent p-4 text-white sm:p-7">
                                    <p class="text-xs font-bold uppercase tracking-wide text-purple-100">Hands-on learning</p>
                                    <p class="mt-2 max-w-xs text-lg font-bold leading-tight sm:text-2xl">Training built around real caregiving routines.</p>
                                </div>
                            </div>

                            <div class="hero-slide absolute inset-0 bg-gradient-to-br from-purple-50 via-white to-slate-100 p-5 sm:p-7">
                                <div class="flex h-full flex-col justify-between">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wide text-purple-600">Applicant tracking</p>
                                        <h2 class="mt-3 max-w-sm text-xl font-bold leading-tight text-slate-900 sm:text-3xl">Submit your profile, documents, and signature in one flow.</h2>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="rounded-xl border border-purple-100 bg-white p-3 shadow-sm sm:rounded-2xl sm:p-4">
                                            <div class="flex items-center justify-between gap-4">
                                                <div>
                                                    <p class="text-sm font-bold text-slate-900">Learner profile</p>
                                                    <p class="mt-1 text-xs text-slate-500">Personal, address, education</p>
                                                </div>
                                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Ready</span>
                                            </div>
                                        </div>
                                        <div class="rounded-xl border border-purple-100 bg-white p-3 shadow-sm sm:rounded-2xl sm:p-4">
                                            <div class="flex items-center justify-between gap-4">
                                                <div>
                                                    <p class="text-sm font-bold text-slate-900">Uploads</p>
                                                    <p class="mt-1 text-xs text-slate-500">Birth certificate, ID photo, diploma</p>
                                                </div>
                                                <span class="rounded-full bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700">Secure</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="hero-slide absolute inset-0 bg-gradient-to-br from-slate-900 via-purple-950 to-slate-800 p-5 text-white sm:p-7">
                                <div class="flex h-full flex-col justify-between">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wide text-purple-200">Admin review</p>
                                        <h2 class="mt-3 max-w-sm text-xl font-bold leading-tight sm:text-3xl">Move applicants from submitted to pre-enlistment.</h2>
                                    </div>
                                    <div class="grid grid-cols-1 gap-3">
                                        <div class="rounded-xl border border-white/10 bg-white/10 p-3 backdrop-blur sm:rounded-2xl sm:p-4">
                                            <p class="text-xs font-semibold text-purple-100">Current status</p>
                                            <p class="mt-1 text-lg font-bold sm:text-xl">Pre-enlistment</p>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="rounded-xl border border-white/10 bg-white/10 p-3 sm:rounded-2xl sm:p-4">
                                                <p class="text-xl font-bold sm:text-2xl">3</p>
                                                <p class="mt-1 text-xs text-purple-100">Review decisions</p>
                                            </div>
                                            <div class="rounded-xl border border-white/10 bg-white/10 p-3 sm:rounded-2xl sm:p-4">
                                                <p class="text-xl font-bold sm:text-2xl">5</p>
                                                <p class="mt-1 text-xs text-purple-100">Required files</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="absolute right-4 top-4 flex gap-2 rounded-full bg-white/85 px-3 py-2 shadow-sm backdrop-blur sm:right-5 sm:top-5">
                                <span class="hero-dot h-2 rounded-full"></span>
                                <span class="hero-dot h-2 rounded-full"></span>
                                <span class="hero-dot h-2 rounded-full"></span>
                            </div>
                        </div>
                    </div>
                    <div class="phone-soft-card relative mx-2 mt-4 rounded-2xl border border-slate-100 bg-white p-4 shadow-xl shadow-slate-200 sm:absolute sm:-bottom-7 sm:left-6 sm:right-6 sm:mx-0 sm:mt-0 sm:rounded-3xl sm:p-5">
                        <div class="flex items-center gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600 sm:h-12 sm:w-12 sm:rounded-2xl">
                                <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 sm:h-6 sm:w-6" aria-hidden="true">
                                    <path d="M8 12.5l2.5 2.5L16.5 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 21a9 9 0 100-18 9 9 0 000 18z" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900">Applicant profile ready</p>
                                <p class="mt-1 text-xs text-slate-500 sm:text-sm">Create your account during enrollment.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-y border-slate-100 bg-slate-50/85 backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 py-7 sm:px-6 sm:py-10 lg:px-8">
                <p class="text-center text-xs font-semibold leading-5 text-slate-500 sm:text-left sm:text-sm">Designed for training operations inspired by trusted healthcare education platforms</p>
                <div class="mt-5 grid grid-cols-2 gap-3 sm:mt-6 sm:grid-cols-4 sm:gap-4">
                    <div class="phone-soft-card rounded-xl border border-slate-100 bg-white px-4 py-3 text-center text-xs font-bold text-slate-700 sm:rounded-2xl sm:px-5 sm:py-4 sm:text-sm">Admissions</div>
                    <div class="phone-soft-card rounded-xl border border-slate-100 bg-white px-4 py-3 text-center text-xs font-bold text-slate-700 sm:rounded-2xl sm:px-5 sm:py-4 sm:text-sm">Programs</div>
                    <div class="phone-soft-card rounded-xl border border-slate-100 bg-white px-4 py-3 text-center text-xs font-bold text-slate-700 sm:rounded-2xl sm:px-5 sm:py-4 sm:text-sm">Schedules</div>
                    <div class="phone-soft-card rounded-xl border border-slate-100 bg-white px-4 py-3 text-center text-xs font-bold text-slate-700 sm:rounded-2xl sm:px-5 sm:py-4 sm:text-sm">Career Hub</div>
                </div>
            </div>
        </section>

        <section id="programs" class="bg-white/90 py-14 backdrop-blur sm:py-32">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="phone-section-heading max-w-3xl">
                    <p class="text-sm font-bold uppercase text-purple-600">Our programs</p>
                    <h2 class="mt-3 text-2xl font-bold leading-tight text-slate-900 sm:mt-4 sm:text-5xl">Caregiving education for local and international readiness.</h2>
                    <p class="mt-4 text-sm leading-6 text-slate-600 sm:mt-6 sm:text-lg sm:leading-8">Structured course cards follow the clearer course discovery style used by modern training centers: direct titles, practical outcomes, and fast enrollment access.</p>
                </div>

                <div class="mt-8 grid grid-cols-1 gap-4 sm:mt-14 sm:gap-6 lg:grid-cols-3">
                    <article class="phone-soft-card rounded-xl border border-slate-100 bg-white p-5 shadow-sm transition-all hover:-translate-y-1 hover:shadow-lg sm:p-8">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600 sm:h-12 sm:w-12 sm:rounded-2xl">
                            <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 sm:h-6 sm:w-6" aria-hidden="true">
                                <path d="M5 20V7a2 2 0 012-2h10a2 2 0 012 2v13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M8 9h8M8 13h6M5 20h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3 class="mt-5 text-xl font-bold text-slate-900 sm:mt-7 sm:text-2xl">Caregiving NC II</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600 sm:mt-4 sm:text-base sm:leading-7">Core caregiving preparation with practical training, patient support routines, documentation, and assessment readiness.</p>
                        <a href="#admissions" class="mt-5 inline-flex text-sm font-semibold text-purple-600 hover:text-purple-700 sm:mt-8">Enroll in this program</a>
                    </article>

                    <article class="phone-soft-card rounded-xl border border-slate-100 bg-white p-5 shadow-sm transition-all hover:-translate-y-1 hover:shadow-lg sm:p-8">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600 sm:h-12 sm:w-12 sm:rounded-2xl">
                            <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 sm:h-6 sm:w-6" aria-hidden="true">
                                <path d="M12 21s-7-4.35-7-10a4 4 0 017-2.65A4 4 0 0119 11c0 5.65-7 10-7 10z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M9 12h2l1-2 1.5 4 1-2H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3 class="mt-5 text-xl font-bold text-slate-900 sm:mt-7 sm:text-2xl">Basic Life Support</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600 sm:mt-4 sm:text-base sm:leading-7">A focused module for emergency awareness, response confidence, and healthcare-adjacent caregiving preparation.</p>
                        <a href="#admissions" class="mt-5 inline-flex text-sm font-semibold text-purple-600 hover:text-purple-700 sm:mt-8">View training path</a>
                    </article>

                    <article class="phone-soft-card rounded-xl border border-slate-100 bg-white p-5 shadow-sm transition-all hover:-translate-y-1 hover:shadow-lg sm:p-8">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600 sm:h-12 sm:w-12 sm:rounded-2xl">
                            <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 sm:h-6 sm:w-6" aria-hidden="true">
                                <path d="M4 7h16v11a2 2 0 01-2 2H6a2 2 0 01-2-2V7z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2M9 13h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3 class="mt-5 text-xl font-bold text-slate-900 sm:mt-7 sm:text-2xl">Alumni Career Hub</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600 sm:mt-4 sm:text-base sm:leading-7">A capstone-ready layer for graduate tracking, career support, employment status updates, and partner opportunities.</p>
                        <a href="#why" class="mt-5 inline-flex text-sm font-semibold text-purple-600 hover:text-purple-700 sm:mt-8">See career support</a>
                    </article>
                </div>
            </div>
        </section>

        <section id="admissions" class="bg-slate-50/90 py-14 backdrop-blur sm:py-32">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 px-4 sm:gap-12 sm:px-6 lg:grid-cols-2 lg:px-8">
                <div>
                    <p class="text-sm font-bold uppercase text-purple-600">Admissions</p>
                    <h2 class="mt-3 text-2xl font-bold leading-tight text-slate-900 sm:mt-4 sm:text-5xl">A simple enrollment flow before the admin review.</h2>
                    <p class="mt-4 text-sm leading-6 text-slate-600 sm:mt-6 sm:text-lg sm:leading-8">Applicants now complete a direct TESDA-inspired learner profile for Caregiving NC II while Google OAuth is paused during development.</p>
                    <div class="mt-6 sm:mt-10">
                        @auth
                            <a href="{{ $accountCtaUrl }}" class="inline-flex w-full items-center justify-center rounded-full bg-purple-600 px-7 py-3.5 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700 sm:w-auto">{{ $accountCtaLabel }}</a>
                        @else
                            <a href="{{ route('enrollment.create') }}" class="inline-flex w-full items-center justify-center rounded-full bg-purple-600 px-7 py-3.5 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700 sm:w-auto">Start Enrollment</a>
                        @endauth
                    </div>
                </div>

                <div class="space-y-3 sm:space-y-4">
                    <div class="phone-soft-card rounded-xl border border-slate-100 bg-white p-4 shadow-sm sm:rounded-2xl sm:p-6">
                        <div class="flex gap-3 sm:gap-5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-purple-50 text-sm font-bold text-purple-600 sm:h-10 sm:w-10">1</span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 sm:text-lg">Verify with Google</h3>
                                <p class="mt-1.5 text-sm leading-6 text-slate-600 sm:mt-2 sm:text-base sm:leading-7">The account establishes the applicant name and email before enrollment begins.</p>
                            </div>
                        </div>
                    </div>
                    <div class="phone-soft-card rounded-xl border border-slate-100 bg-white p-4 shadow-sm sm:rounded-2xl sm:p-6">
                        <div class="flex gap-3 sm:gap-5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-purple-50 text-sm font-bold text-purple-600 sm:h-10 sm:w-10">2</span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 sm:text-lg">Complete applicant details</h3>
                                <p class="mt-1.5 text-sm leading-6 text-slate-600 sm:mt-2 sm:text-base sm:leading-7">Personal information, address, education, contact details, and preferred schedule are saved for review.</p>
                            </div>
                        </div>
                    </div>
                    <div class="phone-soft-card rounded-xl border border-slate-100 bg-white p-4 shadow-sm sm:rounded-2xl sm:p-6">
                        <div class="flex gap-3 sm:gap-5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-purple-50 text-sm font-bold text-purple-600 sm:h-10 sm:w-10">3</span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 sm:text-lg">Admin approval next</h3>
                                <p class="mt-1.5 text-sm leading-6 text-slate-600 sm:mt-2 sm:text-base sm:leading-7">Document verification, payment review, training access, and certificate records can be built after the flow is confirmed.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="why" class="bg-white/90 py-14 backdrop-blur sm:py-32">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-8 sm:gap-12 lg:grid-cols-3">
                    <div>
                        <p class="text-sm font-bold uppercase text-purple-600">Why MCARE</p>
                        <h2 class="mt-3 text-2xl font-bold leading-tight text-slate-900 sm:mt-4 sm:text-4xl">Built like a real training operations hub.</h2>
                    </div>
                    <div class="lg:col-span-2">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5">
                            <div class="phone-soft-card rounded-xl border border-slate-100 bg-white p-5 shadow-sm sm:p-7">
                                <h3 class="text-base font-bold text-slate-900 sm:text-lg">Clear schedules</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600 sm:mt-3 sm:text-base sm:leading-7">Support for weekday, weekend, and special training batches.</p>
                            </div>
                            <div class="phone-soft-card rounded-xl border border-slate-100 bg-white p-5 shadow-sm sm:p-7">
                                <h3 class="text-base font-bold text-slate-900 sm:text-lg">Document readiness</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600 sm:mt-3 sm:text-base sm:leading-7">Prepared for applicant requirements, verification status, and admin notes.</p>
                            </div>
                            <div class="phone-soft-card rounded-xl border border-slate-100 bg-white p-5 shadow-sm sm:p-7">
                                <h3 class="text-base font-bold text-slate-900 sm:text-lg">Learning records</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600 sm:mt-3 sm:text-base sm:leading-7">A foundation for modules, progress tracking, certificates, and digital records.</p>
                            </div>
                            <div class="phone-soft-card rounded-xl border border-slate-100 bg-white p-5 shadow-sm sm:p-7">
                                <h3 class="text-base font-bold text-slate-900 sm:text-lg">Career continuity</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600 sm:mt-3 sm:text-base sm:leading-7">Alumni profiles can continue after training for employment and job placement updates.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="discover" class="relative overflow-hidden border-y border-purple-100 py-14 sm:py-20">
            <!-- Path: resources/views/landing/home.blade.php | Label: Discover bottom radial background layer -->
            <div class="discover-radial-bg absolute inset-0 z-0"></div>
            <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="discover-fade text-sm font-bold uppercase text-purple-600">Discover more</p>
                        <h2 class="discover-fade mt-3 max-w-3xl text-2xl font-bold leading-tight text-slate-900 sm:mt-4 sm:text-4xl">Follow MCARE and watch training updates.</h2>
                        <p class="discover-fade mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:mt-4 sm:text-base sm:leading-7">Connect with the official pages and explore videos from Facebook to learn more about programs, student activities, and enrollment announcements.</p>
                    </div>

                    <div class="discover-fade flex flex-wrap gap-2.5 sm:gap-3">
                        <a href="{{ $socialLinks['facebook'] }}" target="_blank" rel="noopener noreferrer" aria-label="Open MCARE Facebook page" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-purple-100 bg-white text-purple-700 shadow-sm transition hover:-translate-y-0.5 hover:border-purple-200 hover:bg-purple-50 hover:text-purple-800 sm:h-12 sm:w-12">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                <path d="M14 8.5h2V5.2c-.35-.05-1.55-.15-2.95-.15-2.9 0-4.9 1.82-4.9 5.18v2.92H5v3.7h3.15V24h3.88v-7.15h3.03l.48-3.7h-3.51v-2.55c0-1.07.29-2.1 1.97-2.1Z"/>
                            </svg>
                        </a>
                        <a href="{{ $socialLinks['instagram'] }}" target="_blank" rel="noopener noreferrer" aria-label="Open MCARE Instagram page" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-purple-100 bg-white text-purple-700 shadow-sm transition hover:-translate-y-0.5 hover:border-purple-200 hover:bg-purple-50 hover:text-purple-800 sm:h-12 sm:w-12">
                            <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" aria-hidden="true">
                                <rect x="4" y="4" width="16" height="16" rx="5" stroke="currentColor" stroke-width="2"/>
                                <circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="2"/>
                                <circle cx="16.8" cy="7.2" r="1" fill="currentColor"/>
                            </svg>
                        </a>
                        <a href="{{ $socialLinks['youtube'] }}" target="_blank" rel="noopener noreferrer" aria-label="Open MCARE YouTube channel" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-purple-100 bg-white text-purple-700 shadow-sm transition hover:-translate-y-0.5 hover:border-purple-200 hover:bg-purple-50 hover:text-purple-800 sm:h-12 sm:w-12">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6" aria-hidden="true">
                                <path d="M21.6 7.2a3 3 0 0 0-2.1-2.1C17.65 4.6 12 4.6 12 4.6s-5.65 0-7.5.5a3 3 0 0 0-2.1 2.1A31.2 31.2 0 0 0 1.9 12c0 1.6.16 3.2.5 4.8a3 3 0 0 0 2.1 2.1c1.85.5 7.5.5 7.5.5s5.65 0 7.5-.5a3 3 0 0 0 2.1-2.1c.34-1.6.5-3.2.5-4.8 0-1.6-.16-3.2-.5-4.8ZM10 15.4V8.6l5.8 3.4L10 15.4Z"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <div class="mt-8 grid grid-cols-1 gap-4 sm:mt-12 sm:gap-6 lg:grid-cols-3">
                    @foreach ($facebookVideos as $video)
                        <article class="discover-fade phone-soft-card overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm sm:rounded-2xl">
                            <div class="aspect-video bg-slate-100">
                                <iframe
                                    src="https://www.facebook.com/plugins/video.php?href={{ rawurlencode($video['url']) }}&show_text=false&width=560"
                                    class="h-full w-full border-0"
                                    loading="lazy"
                                    allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
                                    allowfullscreen
                                    title="{{ $video['title'] }}">
                                </iframe>
                            </div>
                            <div class="p-4 sm:p-5">
                                <h3 class="text-base font-bold text-slate-900 sm:text-lg">{{ $video['title'] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-500">{{ $video['description'] }}</p>
                                <a href="{{ $video['url'] }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex text-sm font-bold text-purple-700 hover:text-purple-800">Open on Facebook</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="contact" class="border-y border-slate-100 bg-gradient-to-t from-purple-50/90 via-slate-50/90 to-white/85 py-14 backdrop-blur sm:py-20">
            <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-6 px-4 sm:gap-8 sm:px-6 lg:flex-row lg:items-center lg:px-8">
                <div>
                    <p class="text-sm font-bold uppercase text-purple-600">Mission Care</p>
                    <h2 class="mt-3 text-2xl font-bold leading-tight text-slate-900 sm:mt-4 sm:text-4xl">Ready to begin your applicant profile?</h2>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:mt-4 sm:text-base sm:leading-7">Start securely, then proceed to the enrollment form prepared for MCARE training operations.</p>
                </div>
                @auth
                    <a href="{{ $accountCtaUrl }}" class="inline-flex w-full items-center justify-center rounded-full bg-purple-600 px-8 py-4 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700 sm:w-auto">{{ $accountCtaLabel }}</a>
                @else
                    <a href="{{ route('enrollment.create') }}" class="inline-flex w-full items-center justify-center rounded-full bg-purple-600 px-8 py-4 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700 sm:w-auto">Start Enrollment</a>
                @endauth
            </div>
        </section>
    </main>

    <!-- Path: resources/views/landing/home.blade.php | Label: Footer radial gradient area -->
    <footer class="relative z-10 overflow-hidden border-t border-purple-100">
        <div class="footer-radial-bg absolute inset-0 z-0"></div>
        <div class="relative z-10 mx-auto flex max-w-7xl flex-col gap-5 px-4 py-10 text-center text-sm text-slate-700 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-12 sm:text-left lg:px-8">
            <div>
                <p class="font-bold text-slate-900">&copy; {{ date('Y') }} Mission Care Training and Assessment Center.</p>
                <p class="mt-1 text-slate-600">MCARE Hub | Caregiving NC II | Applicant Management</p>
            </div>
            <div class="flex items-center justify-center gap-3">
                <a href="{{ $socialLinks['facebook'] }}" target="_blank" rel="noopener noreferrer" aria-label="Open MCARE Facebook page from footer" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/50 bg-white/85 text-purple-700 shadow-sm hover:bg-white">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                        <path d="M14 8.5h2V5.2c-.35-.05-1.55-.15-2.95-.15-2.9 0-4.9 1.82-4.9 5.18v2.92H5v3.7h3.15V24h3.88v-7.15h3.03l.48-3.7h-3.51v-2.55c0-1.07.29-2.1 1.97-2.1Z"/>
                    </svg>
                </a>
                <a href="{{ $socialLinks['instagram'] }}" target="_blank" rel="noopener noreferrer" aria-label="Open MCARE Instagram page from footer" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/50 bg-white/85 text-purple-700 shadow-sm hover:bg-white">
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" aria-hidden="true">
                        <rect x="4" y="4" width="16" height="16" rx="5" stroke="currentColor" stroke-width="2"/>
                        <circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="2"/>
                        <circle cx="16.8" cy="7.2" r="1" fill="currentColor"/>
                    </svg>
                </a>
                <a href="{{ $socialLinks['youtube'] }}" target="_blank" rel="noopener noreferrer" aria-label="Open MCARE YouTube channel from footer" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/50 bg-white/85 text-purple-700 shadow-sm hover:bg-white">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                        <path d="M21.6 7.2a3 3 0 0 0-2.1-2.1C17.65 4.6 12 4.6 12 4.6s-5.65 0-7.5.5a3 3 0 0 0-2.1 2.1A31.2 31.2 0 0 0 1.9 12c0 1.6.16 3.2.5 4.8a3 3 0 0 0 2.1 2.1c1.85.5 7.5.5 7.5.5s5.65 0 7.5-.5a3 3 0 0 0 2.1-2.1c.34-1.6.5-3.2.5-4.8 0-1.6-.16-3.2-.5-4.8ZM10 15.4V8.6l5.8 3.4L10 15.4Z"/>
                    </svg>
                </a>
            </div>
        </div>
    </footer>

    <!-- Path: resources/views/landing/home.blade.php | Label: Header blur and mobile sidebar script -->
    <script>
        const siteHeader = document.getElementById('site-header');
        const mobileSidebar = document.getElementById('mobile-sidebar');
        const mobileMenuOpen = document.getElementById('mobile-menu-open');
        const mobileMenuClose = document.getElementById('mobile-menu-close');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        const mobileSidebarLinks = document.querySelectorAll('.mobile-sidebar-link');
        const discoverFadeItems = document.querySelectorAll('.discover-fade');

        function updateHeaderScrollState() {
            siteHeader?.classList.toggle('is-scrolled', window.scrollY > 8);
        }

        function openMobileSidebar() {
            mobileSidebar?.classList.add('is-open');
            mobileSidebar?.setAttribute('aria-hidden', 'false');
            mobileMenuOpen?.setAttribute('aria-expanded', 'true');
            document.body.classList.add('overflow-hidden');
        }

        function closeMobileSidebar() {
            mobileSidebar?.classList.remove('is-open');
            mobileSidebar?.setAttribute('aria-hidden', 'true');
            mobileMenuOpen?.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('overflow-hidden');
        }

        function syncResponsiveNavigation() {
            if (window.matchMedia('(min-width: 768px)').matches) {
                closeMobileSidebar();
            }
        }

        function attachDiscoverFadeObserver() {
            if (!discoverFadeItems.length) return;

            if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                discoverFadeItems.forEach((item) => item.classList.add('is-visible'));
                return;
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    entry.target.classList.toggle('is-visible', entry.isIntersecting);
                });
            }, {
                root: null,
                threshold: 0.18,
                rootMargin: '0px 0px -8% 0px',
            });

            discoverFadeItems.forEach((item) => observer.observe(item));
        }

        updateHeaderScrollState();
        attachDiscoverFadeObserver();
        window.addEventListener('scroll', updateHeaderScrollState, { passive: true });
        window.addEventListener('resize', syncResponsiveNavigation);
        mobileMenuOpen?.addEventListener('click', openMobileSidebar);
        mobileMenuClose?.addEventListener('click', closeMobileSidebar);
        mobileMenuOverlay?.addEventListener('click', closeMobileSidebar);
        mobileSidebarLinks.forEach((link) => link.addEventListener('click', closeMobileSidebar));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeMobileSidebar();
        });
    </script>
</body>
</html>
