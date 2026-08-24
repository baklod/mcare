<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caregiving NC II Enrollment | MCARE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Path: resources/views/enrollment/create.blade.php | Label: Phone-first enrollment density */
        @media (max-width: 767px) {
            .enrollment-header-inner {
                padding: 0.875rem 1rem;
                gap: 0.875rem;
            }

            .enrollment-logo {
                width: 2.75rem;
                height: 2.75rem;
                border-radius: 1rem;
            }

            .enrollment-main {
                padding: 1rem;
            }

            .enrollment-hero-card,
            .enrollment-status-card,
            .enrollment-form-shell,
            .enrollment-upload-card,
            .enrollment-consent-card,
            .enrollment-signature-card {
                border-radius: 1.25rem;
                box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
            }

            .enrollment-hero-card {
                padding: 1.125rem;
            }

            .enrollment-status-card {
                padding: 1rem;
            }

            .enrollment-form-shell {
                margin-top: 1rem;
                padding: 1rem;
            }

            .enrollment-form > * + * {
                margin-top: 1.75rem !important;
            }

            .enrollment-section-heading {
                padding-bottom: 0.875rem;
            }

            .enrollment-section-heading h2,
            .enrollment-consent-card h2 {
                font-size: 1.125rem;
                line-height: 1.55rem;
            }

            .enrollment-fields {
                gap: 0.875rem;
                margin-top: 1rem;
            }

            .enrollment-page label {
                margin-bottom: 0.375rem;
                font-size: 0.8125rem;
            }

            .enrollment-page input:not([type="checkbox"]):not([type="radio"]),
            .enrollment-page select {
                min-height: 2.75rem;
                border-radius: 1rem !important;
                padding: 0.7rem 0.875rem;
                font-size: 0.875rem;
            }

            .enrollment-upload-card {
                padding: 1rem;
            }

            .enrollment-upload-zone {
                border-radius: 1rem;
                padding: 1.25rem 0.875rem;
            }

            .enrollment-consent-card {
                padding: 1rem;
            }

            .enrollment-signature-card {
                padding: 1rem;
            }

            .enrollment-signature-switch {
                width: 100%;
                justify-content: center;
            }

            #signature_canvas {
                height: 9.75rem;
            }

            .enrollment-submit-row {
                padding-top: 1rem;
            }
        }

        /* Path: resources/views/enrollment/create.blade.php | Label: Enrollment section jump menu */
        .enrollment-jump-target {
            scroll-margin-top: 6rem;
        }

        .enrollment-jump-panel {
            max-height: min(58vh, 26rem);
            overflow-y: auto;
        }

        .enrollment-form input:not([type="checkbox"]):not([type="radio"]):not([type="file"]):not([type="hidden"]),
        .enrollment-form select,
        .enrollment-form textarea {
            min-height: 3rem;
            font-size: 1rem;
            line-height: 1.5rem;
        }
    </style>
</head>
<body class="enrollment-page min-h-screen bg-white font-sans text-slate-900 antialiased">
    <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-72 bg-gradient-to-b from-purple-100 via-purple-50/70 to-white"></div>
    <div class="pointer-events-none fixed inset-x-0 bottom-0 -z-10 h-72 bg-gradient-to-t from-purple-100 via-purple-50/60 to-white"></div>

    <div id="action-toast" class="fixed right-5 top-5 z-50 hidden max-w-sm rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold leading-6 text-amber-800 shadow-xl shadow-amber-100">
        Too many actions. Please wait for the current request to finish.
    </div>

    <header class="border-b border-purple-100 bg-white/90 backdrop-blur-xl">
        <div class="enrollment-header-inner mx-auto flex max-w-7xl flex-col gap-5 px-6 py-5 sm:flex-row sm:items-center sm:justify-between lg:px-8">
            <a href="{{ route('landing') }}" class="flex items-center gap-4">
                <img src="{{ asset('assets/official-logo.png') }}" alt="Mission Care Training Center logo" class="enrollment-logo h-16 w-16 rounded-2xl object-contain">
                <span>
                    <span class="block text-sm font-bold text-slate-900 sm:text-base">Mission Care Training Center</span>
                    <span class="block text-xs text-slate-500 sm:text-sm">Caregiving NC II Enrollment</span>
                </span>
            </a>
            <a href="{{ route('landing') }}" class="inline-flex h-10 items-center justify-center rounded-full border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:border-purple-200 hover:text-purple-700 sm:h-auto sm:px-5 sm:py-2.5">
                Back to landing
            </a>
        </div>
    </header>

    <main class="enrollment-main mx-auto max-w-7xl px-6 py-10 lg:px-8 lg:py-14">
        <section class="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-[1fr_380px]">
            <div class="enrollment-hero-card rounded-3xl border border-purple-100 bg-white p-7 shadow-xl shadow-purple-100/40 sm:p-10">
                <div class="inline-flex items-center gap-2 rounded-full bg-purple-50 px-3 py-1.5 text-xs font-semibold text-purple-700 ring-1 ring-purple-100 sm:px-4 sm:py-2 sm:text-sm">
                    TESDA-DPA inspired learner profile
                </div>
                <h1 class="mt-5 max-w-4xl text-2xl font-bold leading-tight text-slate-900 sm:mt-7 sm:text-5xl">
                    Caregiving NC II Enrollment Registration
                </h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 sm:mt-5 sm:text-lg sm:leading-8">
                    Complete the learner profile for MCARE's NC II enrollment. Google applicants start with a verified identity, while the remaining TESDA details stay under the applicant's control.
                </p>
            </div>

            <aside class="enrollment-status-card rounded-3xl border border-slate-100 bg-slate-50 p-7 shadow-sm">
                <p class="text-sm font-bold uppercase text-purple-600">Application status</p>
                <div class="mt-4 rounded-2xl bg-white p-4 shadow-sm sm:mt-5 sm:p-5">
                    <p class="text-sm text-slate-500">Program</p>
                    <p class="mt-1 text-lg font-bold text-slate-900 sm:text-xl">Caregiving NC II</p>
                </div>
                <div class="mt-3 rounded-2xl bg-white p-4 shadow-sm sm:mt-4 sm:p-5">
                    <p class="text-sm text-slate-500">Current step</p>
                    <p class="mt-1 text-lg font-bold text-slate-900 sm:text-xl">Learner profile</p>
                </div>
                <div class="mt-3 rounded-2xl bg-white p-4 shadow-sm sm:mt-4 sm:p-5">
                    <p class="text-sm text-slate-500">Enrollment batch</p>
                    <p class="mt-1 text-lg font-bold text-slate-900 sm:text-xl">
                        {{ $enrollmentBatch ? $enrollmentBatch->name.' '.$enrollmentBatch->year : 'Enrollment closed' }}
                    </p>
                    @if ($enrollmentBatch)
                        <p class="mt-2 text-xs font-semibold text-purple-700">{{ $enrollmentBatch->enrollmentStateLabel() }} · {{ $enrollmentBatch->trainingStateLabel() }}</p>
                    @endif
                </div>
                <p class="mt-4 text-sm leading-6 text-slate-500 sm:mt-5">
                    After submission, continue to payment. MCARE will email you as documents and application status are reviewed.
                </p>
            </aside>
        </section>

        <!-- Path: resources/views/enrollment/create.blade.php | Label: Sticky enrollment section jump menu -->
        <section class="enrollment-jump-nav sticky top-0 z-40 mt-4 rounded-2xl border border-purple-100 bg-white/95 shadow-lg shadow-purple-100/50 backdrop-blur-xl">
            <details id="enrollment-jump-details" class="group">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-black text-slate-800 marker:hidden">
                    <span class="flex min-w-0 items-center gap-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-purple-600 text-white">
                            <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" aria-hidden="true">
                                <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>
                            <span class="block leading-5">Jump to section</span>
                            <span class="block text-xs font-semibold text-slate-500">Tap a part instead of scrolling the whole form.</span>
                        </span>
                    </span>
                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-180" aria-hidden="true">
                        <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </summary>
                <nav class="enrollment-jump-panel grid grid-cols-2 gap-2 border-t border-purple-100 p-3 text-sm sm:grid-cols-4" aria-label="Enrollment form sections">
                    <a href="#enrollment-account" class="rounded-xl bg-purple-50 px-3 py-2 font-bold text-purple-700 hover:bg-purple-100">Account</a>
                    <a href="#enrollment-profile" class="rounded-xl bg-slate-50 px-3 py-2 font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Profile</a>
                    <a href="#enrollment-address" class="rounded-xl bg-slate-50 px-3 py-2 font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Address</a>
                    <a href="#enrollment-personal" class="rounded-xl bg-slate-50 px-3 py-2 font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Personal</a>
                    <a href="#enrollment-education" class="rounded-xl bg-slate-50 px-3 py-2 font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Education</a>
                    <a href="#enrollment-classification" class="rounded-xl bg-slate-50 px-3 py-2 font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Classification</a>
                    <a href="#enrollment-documents" class="rounded-xl bg-slate-50 px-3 py-2 font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Documents</a>
                    <a href="#enrollment-signature" class="rounded-xl bg-slate-50 px-3 py-2 font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Signature</a>
                    <a href="#enrollment-submit" class="col-span-2 rounded-xl bg-purple-600 px-3 py-2 text-center font-black text-white hover:bg-purple-700 sm:col-span-4">Submit</a>
                </nav>
            </details>
        </section>

        <section class="enrollment-form-shell mt-8 rounded-3xl border border-slate-100 bg-white p-6 shadow-xl shadow-slate-200/60 sm:p-8">
            @if (! $application && ! $enrollmentBatch)
                <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold leading-6 text-amber-800">
                    This batch is no longer accepting new applications. The form remains visible for reference, but submission will reopen only when an administrator activates a valid enrollment window.
                </div>
            @endif
            @if (session('saved'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold leading-6 text-emerald-700" role="status" aria-live="polite" data-auto-dismiss="5000">
                    {{ session('saved') }}
                </div>
            @endif

            @if (session('reapply_notice'))
                <div class="mb-6 rounded-2xl border border-purple-200 bg-purple-50 px-5 py-4 text-sm font-semibold leading-6 text-purple-900" role="status" aria-live="polite">
                    {{ session('reapply_notice') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold leading-6 text-red-700">
                    Please review the highlighted fields and complete the required information.
                </div>
            @endif

            @if ($application && ($application->admin_notes || $documentFeedback->isNotEmpty()))
                <section class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-950">
                    <p class="text-sm font-black uppercase tracking-wide text-amber-700">Admin review feedback</p>
                    @if ($application->admin_notes)
                        <p class="mt-2 text-sm font-semibold leading-6">{{ $application->admin_notes }}</p>
                    @endif
                    <div class="mt-3 space-y-2">
                        @foreach ($documentFeedback as $key => $feedback)
                            <div class="rounded-xl bg-white/70 px-4 py-3 text-sm">
                                <span class="font-black">{{ $documentLabels[$key] ?? 'Enrollment document' }}:</span>
                                {{ data_get($feedback, 'status') === 'missing' ? 'Missing.' : 'Needs replacement.' }}
                                {{ data_get($feedback, 'note') }}
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs font-semibold text-amber-800">Upload corrected files below and submit the form again. Replaced documents return to admin review automatically.</p>
                </section>
            @endif

            <form method="POST" action="{{ route('enrollment.store') }}" enctype="multipart/form-data" class="enrollment-form space-y-10">
                @csrf

                <div id="enrollment-account" class="enrollment-jump-target">
                    <div class="enrollment-section-heading border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">Account</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Applicant account</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Google sign-in verifies the applicant identity. Browser autofill and saved MCARE details reduce repeat typing without requesting private Google profile data.</p>
                    </div>
                    <div class="enrollment-fields mt-6 grid grid-cols-1 gap-5 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <label for="email" class="mb-2 block text-sm font-semibold text-slate-800">Email address</label>
                            <input id="email" name="email" type="email" inputmode="email" autocomplete="section-applicant email" pattern="^[A-Za-z0-9._%+\-]+@gmail\.com$" value="{{ old('email', $application->email ?? $user?->email ?? '') }}" required @readonly($user) class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100 read-only:cursor-not-allowed read-only:bg-slate-100 read-only:text-slate-600">
                            @if ($isGoogleApplicant)
                                <p class="mt-2 text-xs font-semibold leading-5 text-emerald-700">Verified by Google and locked to this signed-in account.</p>
                            @elseif ($user)
                                <p class="mt-2 text-xs leading-5 text-slate-500">This email is locked to your signed-in MCARE account.</p>
                            @else
                                <p class="mt-2 text-xs leading-5 text-slate-500">Use a Gmail address only. MCARE will email a verification link before account sign-in is allowed.</p>
                            @endif
                            @error('email') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="schedule_preference" class="mb-2 block text-sm font-semibold text-slate-800">Preferred schedule</label>
                            <select id="schedule_preference" name="schedule_preference" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">Select</option>
                                @foreach (['AM' => 'AM class', 'PM' => 'PM class', 'Weekend' => 'Weekend class'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('schedule_preference', $application->schedule_preference ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('schedule_preference') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        @if ($isGoogleApplicant)
                            <div class="md:col-span-2 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                                <p class="text-sm font-bold text-emerald-900">Google account connected</p>
                                <p class="mt-1 text-xs leading-5 text-emerald-800">No separate MCARE password is required. Use Continue with Google whenever you return.</p>
                            </div>
                        @else
                            <div>
                                <label for="password" class="mb-2 block text-sm font-semibold text-slate-800">{{ $user ? 'New password (optional)' : 'Password' }}</label>
                                <input id="password" name="password" type="password" autocomplete="new-password" @required(! $user) class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <div class="mt-3 space-y-1.5 text-xs font-semibold">
                                    <p id="pw-length-check" class="flex items-center gap-2 text-slate-500"><span class="grid h-5 w-5 place-items-center rounded-full border border-slate-300 text-[10px]"> </span> At least 10 characters</p>
                                    <p id="pw-letter-number-check" class="flex items-center gap-2 text-slate-500"><span class="grid h-5 w-5 place-items-center rounded-full border border-slate-300 text-[10px]"> </span> Contains a number</p>
                                    <p id="pw-case-check" class="flex items-center gap-2 text-slate-500"><span class="grid h-5 w-5 place-items-center rounded-full border border-slate-300 text-[10px]"> </span> Contains upper and lowercase letters</p>
                                </div>
                                <p class="mt-2 text-xs leading-5 text-slate-500">{{ $user ? 'Leave blank to keep your current password.' : 'Use a unique passphrase you do not reuse on another website.' }}</p>
                                @error('password') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-800">Confirm password</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" @required(! $user) class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <p id="pw-match-check" class="mt-3 flex items-center gap-2 text-xs font-semibold text-slate-500"><span class="grid h-5 w-5 place-items-center rounded-full border border-slate-300 text-[10px]"> </span> Passwords match</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div id="enrollment-profile" class="enrollment-jump-target">
                    <div class="enrollment-section-heading border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">Learner profile</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Name and contact details</h2>
                    </div>
                    <div class="enrollment-fields mt-6 grid grid-cols-1 gap-5 md:grid-cols-4">
                        <div>
                            <label for="last_name" class="mb-2 block text-sm font-semibold text-slate-800">Last name</label>
                            <input id="last_name" name="last_name" type="text" autocomplete="section-applicant family-name" value="{{ old('last_name', $application->last_name ?? $googleIdentity['last_name']) }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('last_name') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="first_name" class="mb-2 block text-sm font-semibold text-slate-800">First name</label>
                            <input id="first_name" name="first_name" type="text" autocomplete="section-applicant given-name" value="{{ old('first_name', $application->first_name ?? $googleIdentity['first_name']) }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('first_name') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="middle_name" class="mb-2 block text-sm font-semibold text-slate-800">Middle name</label>
                            <input id="middle_name" name="middle_name" type="text" autocomplete="section-applicant additional-name" value="{{ old('middle_name', $application->middle_name ?? $googleIdentity['middle_name']) }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('middle_name') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="extension_name" class="mb-2 block text-sm font-semibold text-slate-800">Extension</label>
                            <input id="extension_name" name="extension_name" type="text" autocomplete="section-applicant honorific-suffix" value="{{ old('extension_name', $application->extension_name ?? '') }}" placeholder="Jr., Sr." class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('extension_name') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="contact_number" class="mb-2 block text-sm font-semibold text-slate-800">Contact number</label>
                            <input id="contact_number" name="contact_number" type="tel" inputmode="tel" autocomplete="section-applicant tel" value="{{ old('contact_number', $application->contact_number ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('contact_number') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="nationality" class="mb-2 block text-sm font-semibold text-slate-800">Nationality</label>
                            <input id="nationality" name="nationality" type="text" autocomplete="section-applicant country-name" value="{{ old('nationality', $application->nationality ?? 'Filipino') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('nationality') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div id="enrollment-address" class="enrollment-jump-target">
                    <div class="enrollment-section-heading border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">Permanent mailing address</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Address information</h2>
                    </div>
                    <div class="enrollment-fields mt-6 grid grid-cols-1 gap-5 md:grid-cols-6">
                        <div class="md:col-span-2">
                            <label for="street" class="mb-2 block text-sm font-semibold text-slate-800">Number, street</label>
                            <input id="street" name="street" type="text" autocomplete="section-address address-line1" maxlength="100" value="{{ old('street', $application->street ?? '') }}" placeholder="e.g. 24 E. Corporal Street, Zone 1" required data-address-field="street" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <p class="mt-2 text-xs leading-5 text-slate-500">Enter only the house/building number, street, and zone. Barangay and city belong in their own fields.</p>
                            @error('street') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="barangay" class="mb-2 block text-sm font-semibold text-slate-800">Barangay</label>
                            <input id="barangay" name="barangay" type="text" autocomplete="section-address address-level3" value="{{ old('barangay', $application->barangay ?? '') }}" required data-address-field="barangay" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('barangay') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="city" class="mb-2 block text-sm font-semibold text-slate-800">City/Municipality</label>
                            <input id="city" name="city" type="text" autocomplete="section-address address-level2" value="{{ old('city', $application->city ?? '') }}" required data-address-field="city" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('city') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="province" class="mb-2 block text-sm font-semibold text-slate-800">Province</label>
                            <input id="province" name="province" type="text" autocomplete="section-address address-level1" value="{{ old('province', $application->province ?? '') }}" required data-address-field="province" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('province') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="region" class="mb-2 block text-sm font-semibold text-slate-800">Region</label>
                            <input id="region" name="region" type="text" autocomplete="off" value="{{ old('region', $application->region ?? '') }}" required data-address-field="region" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('region') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="zip_code" class="mb-2 block text-sm font-semibold text-slate-800">ZIP code</label>
                            <input id="zip_code" name="zip_code" type="text" inputmode="numeric" autocomplete="section-address postal-code" value="{{ old('zip_code', $application->zip_code ?? '') }}" required data-address-field="zip_code" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('zip_code') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div id="enrollment-personal" class="enrollment-jump-target">
                    <div class="enrollment-section-heading border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">Personal information</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Birth, status, and employment</h2>
                    </div>
                    <div class="enrollment-fields mt-6 grid grid-cols-1 gap-5 md:grid-cols-4">
                        <div>
                            <label for="gender" class="mb-2 block text-sm font-semibold text-slate-800">Sex</label>
                            <select id="gender" name="gender" autocomplete="section-applicant sex" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">Select</option>
                                @foreach (['Female', 'Male'] as $option)
                                    <option value="{{ $option }}" @selected(old('gender', $application->gender ?? '') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('gender') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="civil_status" class="mb-2 block text-sm font-semibold text-slate-800">Civil status</label>
                            <select id="civil_status" name="civil_status" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">Select</option>
                                @foreach (['Single', 'Married', 'Separated/Divorced/Annulled', 'Widow/er', 'Common Law/Live-in'] as $option)
                                    <option value="{{ $option }}" @selected(old('civil_status', $application->civil_status ?? '') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('civil_status') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="employment_status" class="mb-2 block text-sm font-semibold text-slate-800">Employment status</label>
                            <select id="employment_status" name="employment_status" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">Select</option>
                                @foreach (['Wage-Employed', 'Underemployed', 'Self-Employed', 'Unemployed'] as $option)
                                    <option value="{{ $option }}" @selected(old('employment_status', $application->employment_status ?? '') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('employment_status') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="employment_type" class="mb-2 block text-sm font-semibold text-slate-800">Employment type</label>
                            <select id="employment_type" name="employment_type" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">None / not applicable</option>
                                @foreach (['Regular', 'Casual', 'Job Order', 'Probationary', 'Permanent', 'Contractual', 'Temporary'] as $option)
                                    <option value="{{ $option }}" @selected(old('employment_type', $application->employment_type ?? '') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('employment_type') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="birth_date" class="mb-2 block text-sm font-semibold text-slate-800">Birthdate</label>
                            <input id="birth_date" name="birth_date" type="date" autocomplete="section-applicant bday" value="{{ old('birth_date', optional($application?->birth_date)->format('Y-m-d')) }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('birth_date') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="birthplace_city" class="mb-2 block text-sm font-semibold text-slate-800">Birthplace city</label>
                            <input id="birthplace_city" name="birthplace_city" type="text" value="{{ old('birthplace_city', $application->birthplace_city ?? '') }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('birthplace_city') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="birthplace_province" class="mb-2 block text-sm font-semibold text-slate-800">Birthplace province</label>
                            <input id="birthplace_province" name="birthplace_province" type="text" value="{{ old('birthplace_province', $application->birthplace_province ?? '') }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('birthplace_province') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="birthplace_region" class="mb-2 block text-sm font-semibold text-slate-800">Birthplace region</label>
                            <input id="birthplace_region" name="birthplace_region" type="text" value="{{ old('birthplace_region', $application->birthplace_region ?? '') }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('birthplace_region') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div id="enrollment-education" class="enrollment-jump-target">
                    <div class="enrollment-section-heading border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">Education and guardian</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Training eligibility details</h2>
                    </div>
                    <div class="enrollment-fields mt-6 grid grid-cols-1 gap-5 md:grid-cols-3">
                        <div>
                            <label for="educational_attainment" class="mb-2 block text-sm font-semibold text-slate-800">Educational attainment</label>
                            <select id="educational_attainment" name="educational_attainment" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">Select</option>
                                @foreach (['No Grade Completed', 'Elementary Undergraduate', 'Elementary Graduate', 'High School Undergraduate', 'High School Graduate', 'Junior High (K-12)', 'Senior High (K-12)', 'Post-Secondary/Technical Vocational Undergraduate', 'Post-Secondary/Technical Vocational Graduate', 'College Undergraduate', 'College Graduate', 'Masteral', 'Doctorate'] as $option)
                                    <option value="{{ $option }}" @selected(old('educational_attainment', $application->educational_attainment ?? '') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('educational_attainment') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="school_name" class="mb-2 block text-sm font-semibold text-slate-800">School name</label>
                            <input id="school_name" name="school_name" type="text" value="{{ old('school_name', $application->school_name ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('school_name') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="year_graduated" class="mb-2 block text-sm font-semibold text-slate-800">Year graduated</label>
                            <input id="year_graduated" name="year_graduated" type="number" min="1950" max="{{ now()->year }}" value="{{ old('year_graduated', $application->year_graduated ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('year_graduated') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="guardian_name" class="mb-2 block text-sm font-semibold text-slate-800">Parent/Guardian name</label>
                            <input id="guardian_name" name="guardian_name" type="text" autocomplete="section-guardian name" value="{{ old('guardian_name', $application->guardian_name ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('guardian_name') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label for="guardian_address" class="mb-2 block text-sm font-semibold text-slate-800">Parent/Guardian permanent address</label>
                            <input id="guardian_address" name="guardian_address" type="text" autocomplete="section-guardian street-address" value="{{ old('guardian_address', $application->guardian_address ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('guardian_address') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div id="enrollment-classification" class="enrollment-jump-target">
                    <div class="enrollment-section-heading border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">TESDA classification</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Optional classification details</h2>
                    </div>
                    <div class="enrollment-fields mt-6 grid grid-cols-1 gap-5 md:grid-cols-4">
                        <div>
                            <label for="classification" class="mb-2 block text-sm font-semibold text-slate-800">Client classification</label>
                            <select id="classification" name="classification" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">None / not specified</option>
                                @foreach (['4Ps Beneficiary', 'Displaced Worker', 'Industry Worker', 'Out-of-School Youth', 'Overseas Filipino Worker', 'Returning/Repatriated OFW', 'Student', 'TESDA Alumni', 'TVET Trainer', 'Victim of Natural Disaster/Calamity', 'Others'] as $option)
                                    <option value="{{ $option }}" @selected(old('classification', $application->classification ?? '') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('classification') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="disability_type" class="mb-2 block text-sm font-semibold text-slate-800">Disability type</label>
                            <select id="disability_type" name="disability_type" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">Not applicable</option>
                                @foreach (['Mental/Intellectual', 'Visual Disability', 'Orthopedic Disability', 'Hearing Disability', 'Speech Impairment', 'Multiple Disabilities', 'Psychosocial Disability', 'Disability Due to Chronic Illness', 'Learning Disability'] as $option)
                                    <option value="{{ $option }}" @selected(old('disability_type', $application->disability_type ?? '') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('disability_type') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="disability_cause" class="mb-2 block text-sm font-semibold text-slate-800">Cause of disability</label>
                            <select id="disability_cause" name="disability_cause" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">Not applicable</option>
                                @foreach (['Congenital/Inborn', 'Illness', 'Injury'] as $option)
                                    <option value="{{ $option }}" @selected(old('disability_cause', $application->disability_cause ?? '') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('disability_cause') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="scholarship_type" class="mb-2 block text-sm font-semibold text-slate-800">Scholarship package</label>
                            <input id="scholarship_type" name="scholarship_type" type="text" value="{{ old('scholarship_type', $application->scholarship_type ?? '') }}" placeholder="TWSP, PESFA, STEP, etc." class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('scholarship_type') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div id="enrollment-documents" class="enrollment-jump-target">
                    <div class="enrollment-section-heading border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">Document upload</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Supporting requirements</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Accepted formats are PDF, JPG, JPEG, and PNG. ID photo accepts JPG or PNG only. Maximum size is 5MB per file.</p>
                    </div>

                    @php
                        $uploadFields = [
                            'birth_certificate' => [
                                'label' => 'Birth Certificate (Photocopy)',
                                'description' => 'Clear PSA/NSO or local civil registrar copy.',
                                'accept' => '.pdf,.jpg,.jpeg,.png',
                                'path' => $application->birth_certificate_path ?? null,
                            ],
                            'education_document' => [
                                'label' => 'Form 137/138 or Diploma',
                                'description' => 'Upload the document that verifies your educational background.',
                                'accept' => '.pdf,.jpg,.jpeg,.png',
                                'path' => $application->education_document_path ?? null,
                            ],
                            'good_moral_certificate' => [
                                'label' => 'Certificate of Good Moral',
                                'description' => 'Upload a readable copy from your school or issuing office.',
                                'accept' => '.pdf,.jpg,.jpeg,.png',
                                'path' => $application->good_moral_certificate_path ?? null,
                            ],
                            'id_photo' => [
                                'label' => '1x1 or 2x2 ID Photo',
                                'description' => 'Use a recent formal ID photo in JPG or PNG format.',
                                'accept' => '.jpg,.jpeg,.png',
                                'path' => $application->id_photo_path ?? null,
                            ],
                        ];

                        // Browsers cannot repopulate a file input after the server
                        // redirects back with validation errors. The controller
                        // stores valid files in a short-lived, session-bound draft;
                        // include that draft in the field state so it is not asked
                        // for again and can be previewed immediately.
                        foreach ($uploadFields as $field => &$details) {
                            $details['draft'] = data_get($draftUploads, $field);
                            $details['has_file'] = filled($details['path']) || filled(data_get($details['draft'], 'path'));
                        }
                        unset($details);
                    @endphp

                    <div class="enrollment-fields mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
                        @foreach ($uploadFields as $field => $details)
                            <div class="enrollment-upload-card rounded-3xl border border-slate-100 bg-slate-50 p-5">
                                <label for="{{ $field }}" class="block text-sm font-bold text-slate-900">{{ $details['label'] }}</label>
                                <p class="mt-1 text-xs leading-5 text-slate-500">{{ $details['description'] }}</p>
                                <div data-upload-zone class="enrollment-upload-zone relative mt-4 rounded-2xl border-2 border-dashed border-purple-200 bg-white px-5 py-7 text-center transition hover:border-purple-400 hover:bg-purple-50/50">
                                    <input id="{{ $field }}" name="{{ $field }}" type="file" accept="{{ $details['accept'] }}" @required(! $details['has_file']) class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0">
                                    <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-purple-50 text-purple-600">
                                        <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" aria-hidden="true">
                                            <path d="M12 16V4m0 0L8 8m4-4 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                    <p class="mt-3 text-sm font-bold text-purple-700">Click to upload or drag and drop</p>
                                    <p data-upload-name class="mt-1 text-xs font-semibold text-slate-500">
                                        @if ($details['draft'])
                                            Preserved: {{ data_get($details['draft'], 'name', 'uploaded file') }}. Upload a new file to replace it.
                                        @elseif ($details['path'])
                                            Previously uploaded. Upload a new file to replace it.
                                        @else
                                            No file selected
                                        @endif
                                    </p>
                                    <div data-upload-preview class="mt-4 hidden overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-2 text-left"></div>
                                    @if ($details['draft'])
                                        <div data-draft-preview class="mt-4 overflow-hidden rounded-xl border border-emerald-200 bg-emerald-50/40 p-2 text-left">
                                            <p class="mb-2 text-[11px] font-black uppercase tracking-wide text-emerald-700">Preserved upload preview</p>
                                            @if (str_starts_with((string) data_get($details['draft'], 'mime'), 'image/'))
                                                <img src="{{ route('enrollment.drafts.content', ['field' => $field]) }}" alt="{{ $details['label'] }} preview" class="max-h-52 w-full rounded-lg object-contain" loading="lazy">
                                            @elseif (data_get($details['draft'], 'mime') === 'application/pdf')
                                                <iframe src="{{ route('enrollment.drafts.content', ['field' => $field]) }}" title="{{ $details['label'] }} preview" class="h-56 w-full rounded-lg bg-white" loading="lazy"></iframe>
                                            @else
                                                <a href="{{ route('enrollment.drafts.content', ['field' => $field]) }}" target="_blank" rel="noopener" class="text-sm font-bold text-purple-700 underline">Open preserved file preview</a>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                @error($field) <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                <div id="enrollment-signature" class="enrollment-consent-card enrollment-jump-target rounded-3xl border border-purple-100 bg-purple-50/70 p-6">
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <h2 class="text-2xl font-bold text-slate-900">Privacy consent and signature</h2>
                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                I attest that I have read and understood the TESDA privacy notice and consent to the processing of my personal information for training, scholarship, employment, survey, and related programs.
                            </p>
                            <label class="mt-5 flex items-start gap-3 text-sm font-semibold leading-6 text-slate-700">
                                <input name="privacy_consent" type="checkbox" value="1" @checked(old('privacy_consent', $application->privacy_consent ?? false)) required class="mt-1 h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                                <span>I agree and certify that the information stated above is true and correct.</span>
                            </label>
                            @error('privacy_consent') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="signature_name" class="mb-2 block text-sm font-semibold text-slate-800">Applicant signature over printed name</label>
                            <input id="signature_name" name="signature_name" type="text" autocomplete="section-applicant name" value="{{ old('signature_name', $application->signature_name ?? $googleIdentity['full_name']) }}" required class="w-full rounded-2xl border border-purple-200 bg-white px-4 py-3 text-sm outline-none focus:border-purple-300 focus:ring-4 focus:ring-purple-100">
                            @error('signature_name') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-3 text-xs leading-5 text-slate-500">Use the same name shown in your drawn or uploaded signature.</p>
                        </div>
                    </div>

                    <div class="enrollment-signature-card mt-6 rounded-3xl border border-purple-100 bg-white p-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-bold uppercase text-purple-600">E-signature method</p>
                                <p class="mt-1 text-sm leading-6 text-slate-500">Draw directly on the pad or upload a clear signature image.</p>
                            </div>
                            <div class="enrollment-signature-switch flex rounded-full border border-purple-100 bg-purple-50 p-1">
                                <label class="cursor-pointer rounded-full px-4 py-2 text-sm font-bold text-slate-700">
                                    <input type="radio" name="signature_type" value="draw" class="mr-1" @checked(old('signature_type', $application->signature_type ?? 'draw') === 'draw')>
                                    Draw
                                </label>
                                <label class="cursor-pointer rounded-full px-4 py-2 text-sm font-bold text-slate-700">
                                    <input type="radio" name="signature_type" value="upload" class="mr-1" @checked(old('signature_type', $application->signature_type ?? 'draw') === 'upload')>
                                    Upload
                                </label>
                            </div>
                        </div>

                        <div id="draw-signature-panel" class="mt-5">
                            <div class="rounded-2xl border-2 border-dashed border-purple-200 bg-slate-50 p-3">
                                <canvas id="signature_canvas" width="900" height="260" class="block h-52 w-full touch-none rounded-xl border border-purple-100 bg-white" aria-label="Draw your signature"></canvas>
                            </div>
                            <input id="signature_data" name="signature_data" type="hidden" value="{{ old('signature_data') }}">
                            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p id="signature_draw_status" class="text-xs font-semibold text-slate-500">{{ ($application->signature_path ?? null) ? 'Previously saved. Draw again to replace it.' : 'No signature drawn yet.' }}</p>
                                <button type="button" id="clear_signature" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:border-purple-200 hover:text-purple-700">
                                    Clear signature
                                </button>
                            </div>
                            @error('signature_data') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div id="upload-signature-panel" class="mt-5 hidden">
                            <div data-upload-zone class="relative rounded-2xl border-2 border-dashed border-purple-200 bg-slate-50 px-5 py-7 text-center transition hover:border-purple-400 hover:bg-purple-50/50">
                                <input id="signature_upload" name="signature_upload" type="file" accept=".jpg,.jpeg,.png" class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0">
                                <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-purple-50 text-purple-600">
                                    <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" aria-hidden="true">
                                        <path d="M12 16V4m0 0L8 8m4-4 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <p class="mt-3 text-sm font-bold text-purple-700">Click to upload signature</p>
                                <p data-upload-name class="mt-1 text-xs font-semibold text-slate-500">
                                    @if (data_get($draftUploads, 'signature_upload'))
                                        Preserved: {{ data_get($draftUploads, 'signature_upload.name', 'signature image') }}. Upload a new image to replace it.
                                    @elseif ($application->signature_path ?? null)
                                        Previously uploaded/saved. Upload a new image to replace it.
                                    @else
                                        No file selected
                                    @endif
                                </p>
                                <div data-upload-preview class="mt-4 hidden overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-2 text-left"></div>
                                @if (data_get($draftUploads, 'signature_upload'))
                                    <div data-draft-preview class="mt-4 overflow-hidden rounded-xl border border-emerald-200 bg-emerald-50/40 p-2 text-left">
                                        <p class="mb-2 text-[11px] font-black uppercase tracking-wide text-emerald-700">Preserved signature preview</p>
                                        <img src="{{ route('enrollment.drafts.content', ['field' => 'signature_upload']) }}" alt="Preserved signature preview" class="max-h-32 w-full rounded-lg object-contain" loading="lazy">
                                    </div>
                                @endif
                            </div>
                            @error('signature_upload') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div id="enrollment-submit" class="enrollment-submit-row enrollment-jump-target border-t border-slate-100 pt-6">
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm leading-6 text-slate-500">Date accomplished will be recorded automatically when the form is submitted.</p>
                        <button type="submit" data-default-label="{{ $application?->status === \App\Models\EnrollmentApplication::STATUS_DENIED ? 'Resubmit corrected enrollment' : 'Submit NC II enrollment' }}" @disabled(! $application && ! $enrollmentBatch) class="inline-flex h-12 items-center justify-center rounded-full bg-purple-600 px-8 text-sm font-bold text-white shadow-lg shadow-purple-100 hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-50 sm:h-auto sm:py-4">
                            {{ $application?->status === \App\Models\EnrollmentApplication::STATUS_DENIED ? 'Resubmit corrected enrollment' : 'Submit NC II enrollment' }}
                        </button>
                    </div>

                    <div id="enrollment-submit-progress" class="mt-4 hidden rounded-2xl border border-purple-200 bg-purple-50 p-4" role="status" aria-live="polite">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 inline-block h-5 w-5 shrink-0 animate-spin rounded-full border-2 border-purple-200 border-t-purple-700" aria-hidden="true"></span>
                            <div>
                                <p id="enrollment-submit-title" class="text-sm font-black text-purple-900">Uploading your enrollment securely...</p>
                                <p id="enrollment-submit-detail" class="mt-1 text-xs font-semibold leading-5 text-purple-700">Keep this page open while the documents upload.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </section>
    </main>

    <script>
        const blockedCharacters = /[<>"'`;{}|\\]/;
        const passwordInput = document.getElementById('password');
        const passwordConfirmationInput = document.getElementById('password_confirmation');
        const emailInput = document.getElementById('email');
        const enrollmentForm = document.querySelector('form[action="{{ route('enrollment.store') }}"]');
        const actionToast = document.getElementById('action-toast');
        const enrollmentJumpDetails = document.getElementById('enrollment-jump-details');
        const enrollmentSubmitButton = enrollmentForm?.querySelector('button[type="submit"]');
        const enrollmentSubmitProgress = document.getElementById('enrollment-submit-progress');
        const enrollmentSubmitTitle = document.getElementById('enrollment-submit-title');
        const enrollmentSubmitDetail = document.getElementById('enrollment-submit-detail');
        const existingSignatureSaved = @json((bool) ($application->signature_path ?? false));
        const serverErrorFields = @json($errors->keys());
        let signatureDrawn = false;
        let enrollmentSubmitStartedAt = null;
        let enrollmentSubmitTimer = null;

        function showActionToast(message) {
            if (!actionToast) return;
            actionToast.textContent = message;
            actionToast.classList.remove('hidden');
            window.clearTimeout(window.mcareEnrollmentToastTimer);
            window.mcareEnrollmentToastTimer = window.setTimeout(() => actionToast.classList.add('hidden'), 2800);
        }

        function formatFileSize(bytes) {
            if (!bytes) return '0 MB';

            return `${(bytes / (1024 * 1024)).toFixed(bytes >= 1024 * 1024 ? 1 : 2)} MB`;
        }

        function setCheckState(elementId, isValid) {
            const row = document.getElementById(elementId);
            if (!row) return;

            const icon = row.querySelector('span');
            row.classList.toggle('text-emerald-700', isValid);
            row.classList.toggle('text-slate-500', !isValid);
            icon.classList.toggle('border-emerald-500', isValid);
            icon.classList.toggle('bg-emerald-500', isValid);
            icon.classList.toggle('text-white', isValid);
            icon.classList.toggle('border-slate-300', !isValid);
            icon.classList.toggle('bg-transparent', !isValid);
            icon.textContent = isValid ? '✓' : '';
        }

        function updatePasswordChecks() {
            const password = passwordInput?.value || '';
            const confirmation = passwordConfirmationInput?.value || '';

            setCheckState('pw-length-check', password.length >= 10);
            setCheckState('pw-letter-number-check', /\d/.test(password));
            setCheckState('pw-case-check', /[a-z]/.test(password) && /[A-Z]/.test(password));
            setCheckState('pw-match-check', password.length > 0 && password === confirmation);

            // Passwords may use punctuation; server-side Password rules handle
            // strength while the client only mirrors the match indicator.
            passwordInput?.setCustomValidity('');

            if (passwordConfirmationInput) {
                passwordConfirmationInput.setCustomValidity(
                    confirmation.length > 0 && password !== confirmation
                        ? 'Passwords do not match.'
                        : ''
                );
            }
        }

        function attachInputHardening() {
            document.querySelectorAll('input[type="text"]').forEach((input) => {
                input.addEventListener('input', () => {
                    input.setCustomValidity(blockedCharacters.test(input.value)
                        ? 'This field contains a blocked security character.'
                        : ''
                    );
                });
            });

            emailInput?.addEventListener('input', () => {
                const email = emailInput.value.trim().toLowerCase();
                emailInput.value = email;
                emailInput.setCustomValidity(email && !/^[a-z0-9._%+-]+@gmail\.com$/.test(email)
                    ? 'Please use a Gmail address ending in @gmail.com.'
                    : ''
                );
            });

            passwordInput?.addEventListener('input', updatePasswordChecks);
            passwordConfirmationInput?.addEventListener('input', updatePasswordChecks);
            updatePasswordChecks();
        }

        function attachAddressAutofillGuard() {
            const street = document.getElementById('street');
            const addressParts = ['barangay', 'city', 'province', 'region', 'zip_code']
                .map((id) => document.getElementById(id))
                .filter((field) => field);

            if (!street || !addressParts.length) return;

            const normalize = (value) => value.trim().replace(/\s+/g, ' ').toLocaleLowerCase();

            function keepStreetOnly() {
                const value = street.value.trim();
                if (!value.includes(',')) return;

                const knownAddressParts = addressParts
                    .map((field) => normalize(field.value))
                    .filter((part) => part.length > 1);
                if (!knownAddressParts.length) return;

                const segments = value.split(/\s*,\s*/).map((segment) => segment.trim()).filter(Boolean);
                const filtered = segments.filter((segment) => !knownAddressParts.includes(normalize(segment)));

                // Keep a useful value if a browser profile happens to contain
                // one shared string for every address field.
                if (filtered.length && filtered.length !== segments.length) {
                    street.value = filtered.join(', ');
                }
            }

            [...addressParts, street].forEach((field) => {
                ['input', 'change', 'blur'].forEach((eventName) => field.addEventListener(eventName, () => {
                    // Autofill events are dispatched one field at a time. Run
                    // again after the profile has populated the remaining
                    // address fields so Number, street stays self-contained.
                    keepStreetOnly();
                    window.setTimeout(keepStreetOnly, 200);
                    window.setTimeout(keepStreetOnly, 900);
                }));
            });

            window.setTimeout(keepStreetOnly, 900);
        }

        function attachUploadFeedback() {
            document.querySelectorAll('[data-upload-zone] input[type="file"]').forEach((input) => {
                const zone = input.closest('[data-upload-zone]');
                const preview = zone?.querySelector('[data-upload-preview]');
                const draftPreview = zone?.querySelector('[data-draft-preview]');

                function clearPreview() {
                    if (!preview) return;
                    if (preview.dataset.objectUrl) {
                        URL.revokeObjectURL(preview.dataset.objectUrl);
                        delete preview.dataset.objectUrl;
                    }
                    preview.replaceChildren();
                    preview.classList.add('hidden');
                }

                function showPreview(file) {
                    if (!preview || !file) return;

                    clearPreview();
                    const objectUrl = URL.createObjectURL(file);
                    preview.dataset.objectUrl = objectUrl;
                    preview.classList.remove('hidden');

                    const heading = document.createElement('p');
                    heading.className = 'mb-2 text-[11px] font-black uppercase tracking-wide text-purple-700';
                    heading.textContent = 'Attached document preview';
                    preview.appendChild(heading);

                    if (file.type === 'application/pdf') {
                        const frame = document.createElement('iframe');
                        frame.className = 'h-56 w-full rounded-lg bg-white';
                        frame.title = `${file.name} preview`;
                        frame.src = objectUrl;
                        preview.appendChild(frame);
                    } else if (file.type.startsWith('image/')) {
                        const image = document.createElement('img');
                        image.className = 'max-h-64 w-full rounded-lg object-contain';
                        image.alt = `${file.name} preview`;
                        image.src = objectUrl;
                        preview.appendChild(image);
                    } else {
                        const link = document.createElement('a');
                        link.className = 'text-sm font-bold text-purple-700 underline';
                        link.href = objectUrl;
                        link.target = '_blank';
                        link.rel = 'noopener';
                        link.textContent = `Open ${file.name} preview`;
                        preview.appendChild(link);
                    }
                }

                input.addEventListener('change', () => {
                    const name = zone?.querySelector('[data-upload-name]');
                    const file = input.files?.[0];

                    if (!file) {
                        if (name) name.textContent = 'No file selected';
                        zone?.classList.remove('border-emerald-300', 'bg-emerald-50');
                        clearPreview();
                        draftPreview?.classList.remove('hidden');
                        return;
                    }

                    if (file.size > 5 * 1024 * 1024) {
                        input.value = '';
                        input.setCustomValidity('Each uploaded file must not exceed 5MB.');
                        input.reportValidity();
                        if (name) name.textContent = 'No file selected';
                        zone?.classList.remove('border-emerald-300', 'bg-emerald-50');
                        clearPreview();
                        return;
                    }

                    input.setCustomValidity('');
                    if (name) name.textContent = `Selected: ${file.name} (${formatFileSize(file.size)})`;
                    zone?.classList.add('border-emerald-300', 'bg-emerald-50');
                    draftPreview?.classList.add('hidden');
                    showPreview(file);
                });
            });
        }

        function attachSignaturePad() {
            const canvas = document.getElementById('signature_canvas');
            const signatureData = document.getElementById('signature_data');
            const clearButton = document.getElementById('clear_signature');
            const status = document.getElementById('signature_draw_status');
            const drawPanel = document.getElementById('draw-signature-panel');
            const uploadPanel = document.getElementById('upload-signature-panel');
            const radios = document.querySelectorAll('input[name="signature_type"]');
            const restoredSignatureData = signatureData.value;

            if (!canvas || !signatureData) return;

            const context = canvas.getContext('2d');
            let drawing = false;

            function resizeCanvas() {
                const image = signatureDrawn ? canvas.toDataURL('image/png') : null;
                const rect = canvas.getBoundingClientRect();
                const ratio = window.devicePixelRatio || 1;

                canvas.width = Math.max(Math.floor(rect.width * ratio), 1);
                canvas.height = Math.max(Math.floor(rect.height * ratio), 1);
                context.setTransform(ratio, 0, 0, ratio, 0, 0);
                context.lineWidth = 2.5;
                context.lineCap = 'round';
                context.lineJoin = 'round';
                context.strokeStyle = '#1e293b';

                if (image) {
                    const restored = new Image();
                    restored.onload = () => context.drawImage(restored, 0, 0, rect.width, rect.height);
                    restored.src = image;
                }
            }

            function pointFromEvent(event) {
                const rect = canvas.getBoundingClientRect();

                return {
                    x: event.clientX - rect.left,
                    y: event.clientY - rect.top,
                };
            }

            function startDrawing(event) {
                event.preventDefault();
                drawing = true;
                const point = pointFromEvent(event);
                context.beginPath();
                context.moveTo(point.x, point.y);
            }

            function draw(event) {
                if (!drawing) return;

                event.preventDefault();
                const point = pointFromEvent(event);
                context.lineTo(point.x, point.y);
                context.stroke();
                signatureDrawn = true;
                signatureData.value = canvas.toDataURL('image/png');
                if (status) status.textContent = 'Signature captured.';
            }

            function stopDrawing() {
                drawing = false;
                context.beginPath();
            }

            function syncSignatureMode() {
                const mode = document.querySelector('input[name="signature_type"]:checked')?.value || 'draw';
                drawPanel?.classList.toggle('hidden', mode !== 'draw');
                uploadPanel?.classList.toggle('hidden', mode !== 'upload');
            }

            function restoreSignature(data) {
                if (!data || !data.startsWith('data:image/png;base64,')) return;

                const restored = new Image();
                restored.onload = () => {
                    const rect = canvas.getBoundingClientRect();
                    context.drawImage(restored, 0, 0, rect.width, rect.height);
                    signatureDrawn = true;
                    signatureData.value = data;
                    if (status) status.textContent = 'Signature restored. Draw again to replace it.';
                };
                restored.src = data;
            }

            canvas.addEventListener('pointerdown', startDrawing);
            canvas.addEventListener('pointermove', draw);
            canvas.addEventListener('pointerup', stopDrawing);
            canvas.addEventListener('pointerleave', stopDrawing);
            clearButton?.addEventListener('click', () => {
                context.clearRect(0, 0, canvas.width, canvas.height);
                signatureDrawn = false;
                signatureData.value = '';
                if (status) {
                    status.textContent = existingSignatureSaved
                        ? 'Previously saved. Draw again to replace it.'
                        : 'No signature drawn yet.';
                }
            });
            radios.forEach((radio) => radio.addEventListener('change', syncSignatureMode));
            window.addEventListener('resize', resizeCanvas);

            resizeCanvas();
            // A failed validation redirects back to this page. Restore the
            // serialized canvas from flashed old input so the applicant does
            // not have to sign a second time.
            restoreSignature(restoredSignatureData);
            syncSignatureMode();
        }

        function attachSubmitValidation() {
            enrollmentForm?.addEventListener('invalid', (event) => {
                const invalidField = event.target;
                if (!(invalidField instanceof HTMLElement)) return;

                window.clearTimeout(window.mcareEnrollmentInvalidTimer);
                window.mcareEnrollmentInvalidTimer = window.setTimeout(() => {
                    const firstInvalid = enrollmentForm.querySelector(':invalid');
                    if (!(firstInvalid instanceof HTMLElement)) return;

                    const label = firstInvalid.labels?.[0]?.textContent?.trim()
                        || firstInvalid.getAttribute('aria-label')
                        || 'the highlighted field';

                    showActionToast(`Please check ${label} before submitting.`);
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus({ preventScroll: true });
                }, 0);
            }, true);

            enrollmentForm?.addEventListener('submit', (event) => {
                if (enrollmentForm.dataset.submitted === 'true') {
                    event.preventDefault();
                    showActionToast('Too many actions. Please wait for the current request to finish.');
                    return;
                }

                const signatureMode = document.querySelector('input[name="signature_type"]:checked')?.value || 'draw';
                const signatureData = document.getElementById('signature_data');
                const signatureUpload = document.getElementById('signature_upload');

                if (signatureMode === 'draw' && !signatureDrawn && !existingSignatureSaved) {
                    event.preventDefault();
                    const status = document.getElementById('signature_draw_status');
                    if (status) status.textContent = 'Draw your signature before submitting.';
                    document.getElementById('signature_canvas')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }

                if (signatureMode === 'draw' && signatureDrawn && signatureData) {
                    signatureData.value = document.getElementById('signature_canvas').toDataURL('image/png');
                }

                if (signatureMode === 'upload' && !signatureUpload?.files?.length && !existingSignatureSaved) {
                    event.preventDefault();
                    signatureUpload?.setCustomValidity('Upload a signature image or choose Draw Signature.');
                    signatureUpload?.reportValidity();
                } else {
                    signatureUpload?.setCustomValidity('');
                }

                if (!event.defaultPrevented) {
                    enrollmentForm.dataset.submitted = 'true';
                    const uploadBytes = [...enrollmentForm.querySelectorAll('input[type="file"]')]
                        .reduce((total, input) => total + (input.files?.[0]?.size || 0), 0);

                    if (enrollmentSubmitButton) {
                        enrollmentSubmitButton.disabled = true;
                        enrollmentSubmitButton.classList.add('cursor-not-allowed', 'opacity-70');
                        enrollmentSubmitButton.textContent = uploadBytes
                            ? `Uploading ${formatFileSize(uploadBytes)}...`
                            : 'Submitting securely...';
                    }

                    enrollmentSubmitStartedAt = Date.now();
                    enrollmentSubmitProgress?.classList.remove('hidden');
                    if (enrollmentSubmitTitle) {
                        enrollmentSubmitTitle.textContent = uploadBytes
                            ? `Uploading ${formatFileSize(uploadBytes)} securely...`
                            : 'Submitting your enrollment securely...';
                    }
                    if (enrollmentSubmitDetail) {
                        enrollmentSubmitDetail.textContent = 'Keep this page open. You will continue to payment automatically when the upload finishes.';
                    }

                    window.clearInterval(enrollmentSubmitTimer);
                    enrollmentSubmitTimer = window.setInterval(() => {
                        if (!enrollmentSubmitStartedAt || !enrollmentSubmitDetail) return;

                        const elapsedSeconds = Math.max(1, Math.round((Date.now() - enrollmentSubmitStartedAt) / 1000));
                        enrollmentSubmitDetail.textContent = elapsedSeconds >= 30
                            ? `Still working (${elapsedSeconds}s). Phone photo uploads can take a minute; please keep this page open.`
                            : `Upload in progress (${elapsedSeconds}s). You will continue to payment automatically.`;
                    }, 1000);
                }
            });

            window.addEventListener('offline', () => {
                if (enrollmentForm?.dataset.submitted !== 'true') return;

                if (enrollmentSubmitTitle) enrollmentSubmitTitle.textContent = 'Connection lost during upload';
                if (enrollmentSubmitDetail) enrollmentSubmitDetail.textContent = 'Reconnect to the internet and keep this page open. If the page does not continue, go back and submit once more.';
                showActionToast('Your internet connection was interrupted during the upload.');
            });

            window.addEventListener('pageshow', () => {
                if (!enrollmentForm) return;

                enrollmentForm.dataset.submitted = 'false';
                enrollmentSubmitStartedAt = null;
                window.clearInterval(enrollmentSubmitTimer);
                enrollmentSubmitTimer = null;
                enrollmentSubmitProgress?.classList.add('hidden');

                if (enrollmentSubmitButton) {
                    enrollmentSubmitButton.disabled = @json(! $application && ! $enrollmentBatch);
                    enrollmentSubmitButton.classList.remove('cursor-not-allowed', 'opacity-70');
                    enrollmentSubmitButton.textContent = enrollmentSubmitButton.dataset.defaultLabel || 'Submit NC II enrollment';
                }
            });
        }

        function attachEnrollmentJumpMenu() {
            enrollmentJumpDetails?.querySelectorAll('a[href^="#"]').forEach((link) => {
                link.addEventListener('click', () => {
                    enrollmentJumpDetails.open = false;
                });
            });
        }

        function focusFirstServerError() {
            if (!serverErrorFields.length) return;

            const aliases = {
                signature_data: 'signature_canvas',
            };
            const firstField = serverErrorFields
                .map((field) => document.getElementById(aliases[field] || field))
                .find((field) => field);

            if (!firstField) return;

            firstField.setAttribute('aria-invalid', 'true');
            window.setTimeout(() => {
                firstField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                if (typeof firstField.focus === 'function') {
                    firstField.focus({ preventScroll: true });
                }
            }, 80);
        }

        attachInputHardening();
        attachAddressAutofillGuard();
        attachUploadFeedback();
        attachSignaturePad();
        attachEnrollmentJumpMenu();
        attachSubmitValidation();
        focusFirstServerError();
    </script>
</body>
</html>
