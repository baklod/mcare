<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caregiving NC II Enrollment | MCARE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-slate-900 antialiased">
    <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-72 bg-gradient-to-b from-purple-100 via-purple-50/70 to-white"></div>
    <div class="pointer-events-none fixed inset-x-0 bottom-0 -z-10 h-72 bg-gradient-to-t from-purple-100 via-purple-50/60 to-white"></div>

    <header class="border-b border-purple-100 bg-white/90 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl flex-col gap-5 px-6 py-5 sm:flex-row sm:items-center sm:justify-between lg:px-8">
            <a href="{{ route('landing') }}" class="flex items-center gap-4">
                <img src="{{ asset('assets/official-logo.png') }}" alt="Mission Care Training Center logo" class="h-16 w-16 rounded-2xl object-contain">
                <span>
                    <span class="block text-base font-bold text-slate-900">Mission Care Training Center</span>
                    <span class="block text-sm text-slate-500">Caregiving NC II Enrollment</span>
                </span>
            </a>
            <a href="{{ route('landing') }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:border-purple-200 hover:text-purple-700">
                Back to landing
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-6 py-10 lg:px-8 lg:py-14">
        <section class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_380px]">
            <div class="rounded-3xl border border-purple-100 bg-white p-7 shadow-xl shadow-purple-100/40 sm:p-10">
                <div class="inline-flex items-center gap-2 rounded-full bg-purple-50 px-4 py-2 text-sm font-semibold text-purple-700 ring-1 ring-purple-100">
                    TESDA-DPA inspired learner profile
                </div>
                <h1 class="mt-7 max-w-4xl text-4xl font-bold leading-tight text-slate-900 sm:text-5xl">
                    Caregiving NC II Enrollment Registration
                </h1>
                <p class="mt-5 max-w-3xl text-lg leading-8 text-slate-600">
                    Complete the learner profile for MCARE's NC II enrollment. This version uses direct applicant registration while Google OAuth is paused during development.
                </p>
            </div>

            <aside class="rounded-3xl border border-slate-100 bg-slate-50 p-7 shadow-sm">
                <p class="text-sm font-bold uppercase text-purple-600">Application status</p>
                <div class="mt-5 rounded-2xl bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Program</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">Caregiving NC II</p>
                </div>
                <div class="mt-4 rounded-2xl bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Current step</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">Learner profile</p>
                </div>
                <p class="mt-5 text-sm leading-6 text-slate-500">
                    Documents, payment review, and admin verification will follow after this base registration is stable.
                </p>
            </aside>
        </section>

        <section class="mt-8 rounded-3xl border border-slate-100 bg-white p-6 shadow-xl shadow-slate-200/60 sm:p-8">
            @if (session('saved'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold leading-6 text-emerald-700">
                    {{ session('saved') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold leading-6 text-red-700">
                    Please review the highlighted fields and complete the required information.
                </div>
            @endif

            <form method="POST" action="{{ route('enrollment.store') }}" enctype="multipart/form-data" class="space-y-10">
                @csrf

                <div>
                    <div class="border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">Account</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Applicant account</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">This replaces Google OAuth for now. The email becomes the applicant account used for enrollment tracking.</p>
                    </div>
                    <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <label for="email" class="mb-2 block text-sm font-semibold text-slate-800">Email address</label>
                            <input id="email" name="email" type="email" inputmode="email" pattern="^[A-Za-z0-9._%+\-]+@gmail\.com$" value="{{ old('email', $application->email ?? $user?->email ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <p class="mt-2 text-xs leading-5 text-slate-500">Use a Gmail address only, for example name@gmail.com.</p>
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
                        <div>
                            <label for="password" class="mb-2 block text-sm font-semibold text-slate-800">Password</label>
                            <input id="password" name="password" type="password" autocomplete="new-password" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <div class="mt-3 space-y-1.5 text-xs font-semibold">
                                <p id="pw-length-check" class="flex items-center gap-2 text-slate-500"><span class="grid h-5 w-5 place-items-center rounded-full border border-slate-300 text-[10px]"> </span> At least 8 characters</p>
                                <p id="pw-letter-number-check" class="flex items-center gap-2 text-slate-500"><span class="grid h-5 w-5 place-items-center rounded-full border border-slate-300 text-[10px]"> </span> Contains letters and numbers</p>
                                <p id="pw-safe-check" class="flex items-center gap-2 text-slate-500"><span class="grid h-5 w-5 place-items-center rounded-full border border-slate-300 text-[10px]"> </span> No blocked security characters</p>
                            </div>
                            <p class="mt-2 text-xs leading-5 text-slate-500">Special characters are allowed except: &lt; &gt; quotes, backticks, semicolons, braces, pipes, and backslashes.</p>
                            @error('password') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-800">Confirm password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <p id="pw-match-check" class="mt-3 flex items-center gap-2 text-xs font-semibold text-slate-500"><span class="grid h-5 w-5 place-items-center rounded-full border border-slate-300 text-[10px]"> </span> Passwords match</p>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">Learner profile</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Name and contact details</h2>
                    </div>
                    <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-4">
                        <div>
                            <label for="last_name" class="mb-2 block text-sm font-semibold text-slate-800">Last name</label>
                            <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $application->last_name ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('last_name') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="first_name" class="mb-2 block text-sm font-semibold text-slate-800">First name</label>
                            <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $application->first_name ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('first_name') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="middle_name" class="mb-2 block text-sm font-semibold text-slate-800">Middle name</label>
                            <input id="middle_name" name="middle_name" type="text" value="{{ old('middle_name', $application->middle_name ?? '') }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('middle_name') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="extension_name" class="mb-2 block text-sm font-semibold text-slate-800">Extension</label>
                            <input id="extension_name" name="extension_name" type="text" value="{{ old('extension_name', $application->extension_name ?? '') }}" placeholder="Jr., Sr." class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('extension_name') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="contact_number" class="mb-2 block text-sm font-semibold text-slate-800">Contact number</label>
                            <input id="contact_number" name="contact_number" type="text" value="{{ old('contact_number', $application->contact_number ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('contact_number') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="nationality" class="mb-2 block text-sm font-semibold text-slate-800">Nationality</label>
                            <input id="nationality" name="nationality" type="text" value="{{ old('nationality', $application->nationality ?? 'Filipino') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('nationality') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <div class="border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">Permanent mailing address</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Address information</h2>
                    </div>
                    <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-6">
                        <div class="md:col-span-2">
                            <label for="street" class="mb-2 block text-sm font-semibold text-slate-800">Number, street</label>
                            <input id="street" name="street" type="text" value="{{ old('street', $application->street ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('street') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="barangay" class="mb-2 block text-sm font-semibold text-slate-800">Barangay</label>
                            <input id="barangay" name="barangay" type="text" value="{{ old('barangay', $application->barangay ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('barangay') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="city" class="mb-2 block text-sm font-semibold text-slate-800">City/Municipality</label>
                            <input id="city" name="city" type="text" value="{{ old('city', $application->city ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('city') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="province" class="mb-2 block text-sm font-semibold text-slate-800">Province</label>
                            <input id="province" name="province" type="text" value="{{ old('province', $application->province ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('province') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="region" class="mb-2 block text-sm font-semibold text-slate-800">Region</label>
                            <input id="region" name="region" type="text" value="{{ old('region', $application->region ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('region') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="zip_code" class="mb-2 block text-sm font-semibold text-slate-800">ZIP code</label>
                            <input id="zip_code" name="zip_code" type="text" value="{{ old('zip_code', $application->zip_code ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('zip_code') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <div class="border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">Personal information</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Birth, status, and employment</h2>
                    </div>
                    <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-4">
                        <div>
                            <label for="gender" class="mb-2 block text-sm font-semibold text-slate-800">Sex</label>
                            <select id="gender" name="gender" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
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
                            <input id="birth_date" name="birth_date" type="date" value="{{ old('birth_date', optional($application?->birth_date)->format('Y-m-d')) }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
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

                <div>
                    <div class="border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">Education and guardian</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Training eligibility details</h2>
                    </div>
                    <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-3">
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
                            <input id="guardian_name" name="guardian_name" type="text" value="{{ old('guardian_name', $application->guardian_name ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('guardian_name') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label for="guardian_address" class="mb-2 block text-sm font-semibold text-slate-800">Parent/Guardian permanent address</label>
                            <input id="guardian_address" name="guardian_address" type="text" value="{{ old('guardian_address', $application->guardian_address ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @error('guardian_address') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <div class="border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">TESDA classification</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Optional classification details</h2>
                    </div>
                    <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-4">
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

                <div>
                    <div class="border-b border-slate-100 pb-5">
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
                    @endphp

                    <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
                        @foreach ($uploadFields as $field => $details)
                            <div class="rounded-3xl border border-slate-100 bg-slate-50 p-5">
                                <label for="{{ $field }}" class="block text-sm font-bold text-slate-900">{{ $details['label'] }}</label>
                                <p class="mt-1 text-xs leading-5 text-slate-500">{{ $details['description'] }}</p>
                                <div data-upload-zone class="relative mt-4 rounded-2xl border-2 border-dashed border-purple-200 bg-white px-5 py-7 text-center transition hover:border-purple-400 hover:bg-purple-50/50">
                                    <input id="{{ $field }}" name="{{ $field }}" type="file" accept="{{ $details['accept'] }}" @required(! $details['path']) class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0">
                                    <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-purple-50 text-purple-600">
                                        <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" aria-hidden="true">
                                            <path d="M12 16V4m0 0L8 8m4-4 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                    <p class="mt-3 text-sm font-bold text-purple-700">Click to upload or drag and drop</p>
                                    <p data-upload-name class="mt-1 text-xs font-semibold text-slate-500">{{ $details['path'] ? 'Previously uploaded. Upload a new file to replace it.' : 'No file selected' }}</p>
                                </div>
                                @error($field) <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl border border-purple-100 bg-purple-50/70 p-6">
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
                            <input id="signature_name" name="signature_name" type="text" value="{{ old('signature_name', $application->signature_name ?? '') }}" required class="w-full rounded-2xl border border-purple-200 bg-white px-4 py-3 text-sm outline-none focus:border-purple-300 focus:ring-4 focus:ring-purple-100">
                            @error('signature_name') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-3 text-xs leading-5 text-slate-500">Use the same name shown in your drawn or uploaded signature.</p>
                        </div>
                    </div>

                    <div class="mt-6 rounded-3xl border border-purple-100 bg-white p-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-bold uppercase text-purple-600">E-signature method</p>
                                <p class="mt-1 text-sm leading-6 text-slate-500">Draw directly on the pad or upload a clear signature image.</p>
                            </div>
                            <div class="flex rounded-full border border-purple-100 bg-purple-50 p-1">
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
                                <p data-upload-name class="mt-1 text-xs font-semibold text-slate-500">{{ ($application->signature_path ?? null) ? 'Previously uploaded/saved. Upload a new image to replace it.' : 'No file selected' }}</p>
                            </div>
                            @error('signature_upload') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm leading-6 text-slate-500">Date accomplished will be recorded automatically when the form is submitted.</p>
                    <button type="submit" class="inline-flex items-center justify-center rounded-full bg-purple-600 px-8 py-4 text-sm font-bold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">
                        Submit NC II enrollment
                    </button>
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
        const existingSignatureSaved = @json((bool) ($application->signature_path ?? false));
        let signatureDrawn = false;

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

            setCheckState('pw-length-check', password.length >= 8);
            setCheckState('pw-letter-number-check', /[A-Za-z]/.test(password) && /\d/.test(password));
            setCheckState('pw-safe-check', password.length > 0 && !blockedCharacters.test(password));
            setCheckState('pw-match-check', password.length > 0 && password === confirmation);

            if (passwordInput) {
                passwordInput.setCustomValidity(blockedCharacters.test(password)
                    ? 'Password contains a blocked security character.'
                    : ''
                );
            }

            if (passwordConfirmationInput) {
                passwordConfirmationInput.setCustomValidity(
                    confirmation.length > 0 && password !== confirmation
                        ? 'Passwords do not match.'
                        : ''
                );
            }
        }

        function attachInputHardening() {
            document.querySelectorAll('input[type="text"], input[type="password"]').forEach((input) => {
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

        function attachUploadFeedback() {
            document.querySelectorAll('[data-upload-zone] input[type="file"]').forEach((input) => {
                input.addEventListener('change', () => {
                    const zone = input.closest('[data-upload-zone]');
                    const name = zone?.querySelector('[data-upload-name]');
                    const file = input.files?.[0];

                    if (!file) {
                        if (name) name.textContent = 'No file selected';
                        zone?.classList.remove('border-emerald-300', 'bg-emerald-50');
                        return;
                    }

                    if (file.size > 5 * 1024 * 1024) {
                        input.value = '';
                        input.setCustomValidity('Each uploaded file must not exceed 5MB.');
                        input.reportValidity();
                        if (name) name.textContent = 'No file selected';
                        zone?.classList.remove('border-emerald-300', 'bg-emerald-50');
                        return;
                    }

                    input.setCustomValidity('');
                    if (name) name.textContent = `Selected: ${file.name}`;
                    zone?.classList.add('border-emerald-300', 'bg-emerald-50');
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
            syncSignatureMode();
        }

        function attachSubmitValidation() {
            enrollmentForm?.addEventListener('submit', (event) => {
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
            });
        }

        attachInputHardening();
        attachUploadFeedback();
        attachSignaturePad();
        attachSubmitValidation();
    </script>
</body>
</html>
