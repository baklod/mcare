<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCARE | Mission Care Training and Assessment Center</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-slate-900 antialiased">
    <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-80 bg-gradient-to-b from-purple-100 via-purple-50/70 to-white"></div>
    <div class="pointer-events-none fixed inset-x-0 bottom-0 -z-10 h-80 bg-gradient-to-t from-purple-100 via-purple-50/60 to-white"></div>
    <header class="sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur-xl">
        <div class="border-b border-purple-100 bg-purple-50">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-2 text-xs font-semibold text-purple-700 lg:px-8">
                <p>TESDA-focused caregiving training with secure online applicant enrollment</p>
                <a href="#programs" class="hidden text-purple-800 hover:text-purple-600 sm:inline-flex">Explore programs</a>
            </div>
        </div>

        <nav class="mx-auto flex h-20 max-w-7xl items-center justify-between px-6 lg:px-8" aria-label="Main navigation">
            <a href="{{ route('landing') }}" class="flex items-center gap-3">
                <img src="{{ asset('assets/official-logo.png') }}" alt="Mission Care Training Center logo" class="h-12 w-12 rounded-2xl object-contain">
                <span>
                    <span class="block text-sm font-bold leading-5 text-slate-900">MCARE</span>
                    <span class="block text-xs leading-4 text-slate-500">Mission Care Training Center</span>
                </span>
            </a>

            <div class="hidden items-center gap-8 md:flex">
                <a href="#programs" class="text-sm font-semibold text-slate-600 hover:text-purple-600">Programs</a>
                <a href="#admissions" class="text-sm font-semibold text-slate-600 hover:text-purple-600">Admissions</a>
                <a href="#why" class="text-sm font-semibold text-slate-600 hover:text-purple-600">Why MCARE</a>
                <a href="#contact" class="text-sm font-semibold text-slate-600 hover:text-purple-600">Contact</a>
            </div>

            <div class="flex items-center gap-3">
                @auth
                    <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                        @csrf
                        <button type="submit" class="rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:border-purple-200 hover:text-purple-700">Sign out</button>
                    </form>
                    <a href="{{ route('enrollment.create') }}" class="rounded-full bg-purple-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">Enroll Now</a>
                @else
                    <a href="{{ route('enrollment.create') }}" class="rounded-full bg-purple-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">Enroll Now</a>
                @endauth

                <details class="relative md:hidden">
                    <summary class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 marker:hidden">
                        <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" aria-hidden="true">
                            <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </summary>
                    <div class="absolute right-0 mt-4 w-56 rounded-2xl border border-slate-100 bg-white p-3 shadow-xl shadow-slate-200">
                        <a href="#programs" class="block rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-purple-50 hover:text-purple-700">Programs</a>
                        <a href="#admissions" class="block rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-purple-50 hover:text-purple-700">Admissions</a>
                        <a href="#why" class="block rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-purple-50 hover:text-purple-700">Why MCARE</a>
                        <a href="#contact" class="block rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-purple-50 hover:text-purple-700">Contact</a>
                    </div>
                </details>
            </div>
        </nav>
    </header>

    <main>
        <section class="bg-gradient-to-b from-purple-50 via-white to-white">
            <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-16 px-6 py-20 lg:grid-cols-2 lg:px-8 lg:py-28">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-purple-50 px-4 py-2 text-sm font-semibold text-purple-700 ring-1 ring-purple-100">
                        <span class="h-2 w-2 rounded-full bg-purple-500"></span>
                        Direct NC II applicant registration
                    </div>

                    <h1 class="mt-8 max-w-3xl text-5xl font-bold leading-none text-slate-900 sm:text-6xl">
                        Build a caregiving career with a training center you can trust.
                    </h1>

                    <p class="mt-7 max-w-2xl text-lg leading-8 text-slate-600">
                        MCARE connects Mission Care applicants to a clearer enrollment pathway, TESDA-oriented caregiving programs, class scheduling, learning records, and future alumni career support.
                    </p>

                    <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                        @auth
                            <a href="{{ route('enrollment.create') }}" class="inline-flex items-center justify-center rounded-full bg-purple-600 px-7 py-3.5 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">Enroll Now</a>
                        @else
                            <a href="{{ route('enrollment.create') }}" class="inline-flex items-center justify-center rounded-full bg-purple-600 px-7 py-3.5 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">Start Enrollment</a>
                        @endauth
                        <a href="#programs" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-7 py-3.5 text-sm font-semibold text-slate-700 hover:border-purple-200 hover:text-purple-700">View Programs</a>
                    </div>

                    <div class="mt-10 grid max-w-xl grid-cols-3 gap-5 border-t border-slate-100 pt-8">
                        <div>
                            <p class="text-2xl font-bold text-slate-900">NC II</p>
                            <p class="mt-1 text-sm leading-6 text-slate-500">Caregiving pathway</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-900">OAuth</p>
                            <p class="mt-1 text-sm leading-6 text-slate-500">Secure entry</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-900">Hub</p>
                            <p class="mt-1 text-sm leading-6 text-slate-500">Training to alumni</p>
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

                <div class="relative">
                    <div class="rounded-[2rem] border border-slate-100 bg-slate-50 p-4 shadow-xl shadow-slate-200">
                        <div class="aspect-[4/5] overflow-hidden rounded-[1.5rem] bg-white">
                            <div class="h-full w-full animate-pulse bg-slate-200"></div>
                        </div>
                    </div>
                    <div class="absolute -bottom-7 left-6 right-6 rounded-3xl border border-slate-100 bg-white p-5 shadow-xl shadow-slate-200">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 text-purple-600">
                                <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" aria-hidden="true">
                                    <path d="M8 12.5l2.5 2.5L16.5 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 21a9 9 0 100-18 9 9 0 000 18z" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900">Applicant profile ready</p>
                            <p class="mt-1 text-sm text-slate-500">Create your account during enrollment.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-y border-slate-100 bg-slate-50">
            <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
                <p class="text-sm font-semibold text-slate-500">Designed for training operations inspired by trusted healthcare education platforms</p>
                <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-2xl border border-slate-100 bg-white px-5 py-4 text-sm font-bold text-slate-700">Admissions</div>
                    <div class="rounded-2xl border border-slate-100 bg-white px-5 py-4 text-sm font-bold text-slate-700">Programs</div>
                    <div class="rounded-2xl border border-slate-100 bg-white px-5 py-4 text-sm font-bold text-slate-700">Schedules</div>
                    <div class="rounded-2xl border border-slate-100 bg-white px-5 py-4 text-sm font-bold text-slate-700">Career Hub</div>
                </div>
            </div>
        </section>

        <section id="programs" class="bg-white py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase text-purple-600">Our programs</p>
                    <h2 class="mt-4 text-4xl font-bold leading-tight text-slate-900 sm:text-5xl">Caregiving education for local and international readiness.</h2>
                    <p class="mt-6 text-lg leading-8 text-slate-600">Structured course cards follow the clearer course discovery style used by modern training centers: direct titles, practical outcomes, and fast enrollment access.</p>
                </div>

                <div class="mt-14 grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <article class="rounded-xl border border-slate-100 bg-white p-8 shadow-sm transition-all hover:-translate-y-1 hover:shadow-lg">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 text-purple-600">
                            <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" aria-hidden="true">
                                <path d="M5 20V7a2 2 0 012-2h10a2 2 0 012 2v13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M8 9h8M8 13h6M5 20h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3 class="mt-7 text-2xl font-bold text-slate-900">Caregiving NC II</h3>
                        <p class="mt-4 leading-7 text-slate-600">Core caregiving preparation with practical training, patient support routines, documentation, and assessment readiness.</p>
                        <a href="#admissions" class="mt-8 inline-flex text-sm font-semibold text-purple-600 hover:text-purple-700">Enroll in this program</a>
                    </article>

                    <article class="rounded-xl border border-slate-100 bg-white p-8 shadow-sm transition-all hover:-translate-y-1 hover:shadow-lg">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 text-purple-600">
                            <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" aria-hidden="true">
                                <path d="M12 21s-7-4.35-7-10a4 4 0 017-2.65A4 4 0 0119 11c0 5.65-7 10-7 10z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M9 12h2l1-2 1.5 4 1-2H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3 class="mt-7 text-2xl font-bold text-slate-900">Basic Life Support</h3>
                        <p class="mt-4 leading-7 text-slate-600">A focused module for emergency awareness, response confidence, and healthcare-adjacent caregiving preparation.</p>
                        <a href="#admissions" class="mt-8 inline-flex text-sm font-semibold text-purple-600 hover:text-purple-700">View training path</a>
                    </article>

                    <article class="rounded-xl border border-slate-100 bg-white p-8 shadow-sm transition-all hover:-translate-y-1 hover:shadow-lg">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 text-purple-600">
                            <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" aria-hidden="true">
                                <path d="M4 7h16v11a2 2 0 01-2 2H6a2 2 0 01-2-2V7z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2M9 13h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3 class="mt-7 text-2xl font-bold text-slate-900">Alumni Career Hub</h3>
                        <p class="mt-4 leading-7 text-slate-600">A capstone-ready layer for graduate tracking, career support, employment status updates, and partner opportunities.</p>
                        <a href="#why" class="mt-8 inline-flex text-sm font-semibold text-purple-600 hover:text-purple-700">See career support</a>
                    </article>
                </div>
            </div>
        </section>

        <section id="admissions" class="bg-slate-50 py-24 sm:py-32">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-12 px-6 lg:grid-cols-2 lg:px-8">
                <div>
                    <p class="text-sm font-bold uppercase text-purple-600">Admissions</p>
                    <h2 class="mt-4 text-4xl font-bold leading-tight text-slate-900 sm:text-5xl">A simple enrollment flow before the admin review.</h2>
                    <p class="mt-6 text-lg leading-8 text-slate-600">Applicants now complete a direct TESDA-inspired learner profile for Caregiving NC II while Google OAuth is paused during development.</p>
                    <div class="mt-10">
                        @auth
                            <a href="{{ route('enrollment.create') }}" class="inline-flex items-center justify-center rounded-full bg-purple-600 px-7 py-3.5 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">Continue Enrollment</a>
                        @else
                            <a href="{{ route('enrollment.create') }}" class="inline-flex items-center justify-center rounded-full bg-purple-600 px-7 py-3.5 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">Start Enrollment</a>
                        @endauth
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                        <div class="flex gap-5">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-purple-50 text-sm font-bold text-purple-600">1</span>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Verify with Google</h3>
                                <p class="mt-2 leading-7 text-slate-600">The account establishes the applicant name and email before enrollment begins.</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                        <div class="flex gap-5">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-purple-50 text-sm font-bold text-purple-600">2</span>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Complete applicant details</h3>
                                <p class="mt-2 leading-7 text-slate-600">Personal information, address, education, contact details, and preferred schedule are saved for review.</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                        <div class="flex gap-5">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-purple-50 text-sm font-bold text-purple-600">3</span>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Admin approval next</h3>
                                <p class="mt-2 leading-7 text-slate-600">Document verification, payment review, training access, and certificate records can be built after the flow is confirmed.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="why" class="bg-white py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-12 lg:grid-cols-3">
                    <div>
                        <p class="text-sm font-bold uppercase text-purple-600">Why MCARE</p>
                        <h2 class="mt-4 text-4xl font-bold leading-tight text-slate-900">Built like a real training operations hub.</h2>
                    </div>
                    <div class="lg:col-span-2">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div class="rounded-xl border border-slate-100 bg-white p-7 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-900">Clear schedules</h3>
                                <p class="mt-3 leading-7 text-slate-600">Support for weekday, weekend, and special training batches.</p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-white p-7 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-900">Document readiness</h3>
                                <p class="mt-3 leading-7 text-slate-600">Prepared for applicant requirements, verification status, and admin notes.</p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-white p-7 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-900">Learning records</h3>
                                <p class="mt-3 leading-7 text-slate-600">A foundation for modules, progress tracking, certificates, and digital records.</p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-white p-7 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-900">Career continuity</h3>
                                <p class="mt-3 leading-7 text-slate-600">Alumni profiles can continue after training for employment and job placement updates.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="contact" class="border-y border-slate-100 bg-gradient-to-t from-purple-50 via-slate-50 to-white py-20">
            <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-8 px-6 lg:flex-row lg:items-center lg:px-8">
                <div>
                    <p class="text-sm font-bold uppercase text-purple-600">Mission Care</p>
                    <h2 class="mt-4 text-4xl font-bold leading-tight text-slate-900">Ready to begin your applicant profile?</h2>
                    <p class="mt-4 max-w-2xl leading-7 text-slate-600">Start securely, then proceed to the enrollment form prepared for MCARE training operations.</p>
                </div>
                @auth
                    <a href="{{ route('enrollment.create') }}" class="inline-flex items-center justify-center rounded-full bg-purple-600 px-8 py-4 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">Enroll Now</a>
                @else
                    <a href="{{ route('enrollment.create') }}" class="inline-flex items-center justify-center rounded-full bg-purple-600 px-8 py-4 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">Start Enrollment</a>
                @endauth
            </div>
        </section>
    </main>

    <footer class="bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-10 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between lg:px-8">
            <p>&copy; {{ date('Y') }} Mission Care Training and Assessment Center.</p>
            <p>MCARE Hub | Caregiving NC II | Applicant Management</p>
        </div>
    </footer>
</body>
</html>
