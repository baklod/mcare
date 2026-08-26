<!DOCTYPE html>
<html lang="en" class="bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Historical Alumni Record | MCARE</title>
    <x-dashboard-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    @php($claimErrors = $errors->getBag('alumniClaim'))
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="{{ route('landing') }}" class="flex min-w-0 items-center gap-3">
                <img src="{{ asset('assets/official-logo.png') }}" alt="MCARE logo" class="h-11 w-11 rounded-xl border border-slate-100 bg-white object-contain p-1 shadow-sm">
                <span class="min-w-0"><strong class="block truncate font-display text-sm text-slate-900 sm:text-base">Mission Care</strong><span class="block truncate text-xs text-slate-500">Historical Alumni Verification</span></span>
            </a>
            <a href="{{ route('login') }}" class="secondary-action shrink-0">Sign in</a>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
        <section class="grid gap-6 lg:grid-cols-[0.72fr_1.28fr] lg:items-start">
            <aside class="space-y-5 lg:sticky lg:top-24">
                <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-purple-800 via-purple-700 to-violet-600 p-6 text-white shadow-xl shadow-purple-200/50 sm:p-8">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-purple-100">For graduates before the MCARE website</p>
                    <h1 class="mt-3 font-display text-3xl font-black leading-tight">Claim your historical alumni record</h1>
                    <p class="mt-4 text-sm leading-6 text-purple-50">This is not a new enrollment. It connects your previous MCARE graduation record to a verified online account.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-display text-lg font-bold">What happens next?</h2>
                    <ol class="mt-4 space-y-4 text-sm text-slate-600">
                        <li class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-purple-100 font-black text-purple-700">1</span><span><strong class="block text-slate-900">Verify your email</strong>MCARE sends a secure link after submission.</span></li>
                        <li class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-purple-100 font-black text-purple-700">2</span><span><strong class="block text-slate-900">Visit MCARE</strong>Bring a valid ID and your original COTC or TOR.</span></li>
                        <li class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-purple-100 font-black text-purple-700">3</span><span><strong class="block text-slate-900">Record verification</strong>An administrator checks the physical documents and MCARE archive.</span></li>
                        <li class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-purple-100 font-black text-purple-700">4</span><span><strong class="block text-slate-900">Alumni access</strong>Once approved, your Career Hub account becomes available.</span></li>
                    </ol>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-900">
                    <strong class="block">Important</strong>
                    Email verification confirms mailbox ownership only. Alumni access is activated only after physical identity, COTC/TOR, and archive verification.
                </div>
            </aside>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
                @if(session('claim_submitted'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm leading-6 text-emerald-900" role="status">
                        <strong class="block">Claim received</strong>{{ session('claim_submitted') }}
                        @if(session('verification_notice'))<span class="mt-1 block">{{ session('verification_notice') }}</span>@endif
                    </div>
                @endif
                @if($claimErrors->any())
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800" role="alert">Please review the highlighted alumni claim fields.</div>
                @endif

                <form method="POST" action="{{ route('alumni.claim.store') }}" enctype="multipart/form-data" class="space-y-8" data-alumni-claim-form>
                    @csrf
                    <section>
                        <div class="border-b border-slate-200 pb-3"><p class="text-xs font-black uppercase tracking-wide text-purple-700">Step 1</p><h2 class="mt-1 font-display text-xl font-bold">Identity and contact details</h2></div>
                        <div class="mt-5 grid gap-4 md:grid-cols-3">
                            <div><label class="form-label" for="first_name">First name</label><input class="form-field" id="first_name" name="first_name" value="{{ old('first_name') }}" required>@error('first_name', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="middle_name">Middle name</label><input class="form-field" id="middle_name" name="middle_name" value="{{ old('middle_name') }}">@error('middle_name', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="last_name">Last name</label><input class="form-field" id="last_name" name="last_name" value="{{ old('last_name') }}" required>@error('last_name', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="birth_date">Birth date</label><input class="form-field" id="birth_date" name="birth_date" type="date" value="{{ old('birth_date') }}" required>@error('birth_date', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="gender">Gender</label><select class="form-field" id="gender" name="gender" required><option value="">Select</option><option value="Male" @selected(old('gender') === 'Male')>Male</option><option value="Female" @selected(old('gender') === 'Female')>Female</option></select>@error('gender', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="contact_number">Contact number</label><input class="form-field" id="contact_number" name="contact_number" value="{{ old('contact_number') }}" required>@error('contact_number', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                        </div>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div><label class="form-label" for="email">Email to verify</label><input class="form-field" id="email" name="email" type="email" value="{{ old('email') }}" required>@error('email', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div></div>
                            <div><label class="form-label" for="password">Create password</label><input class="form-field" id="password" name="password" type="password" required>@error('password', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="password_confirmation">Confirm password</label><input class="form-field" id="password_confirmation" name="password_confirmation" type="password" required></div>
                        </div>
                    </section>

                    <section>
                        <div class="border-b border-slate-200 pb-3"><p class="text-xs font-black uppercase tracking-wide text-purple-700">Step 2</p><h2 class="mt-1 font-display text-xl font-bold">Current address and education</h2></div>
                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                            <div><label class="form-label" for="street">Number and street</label><input class="form-field" id="street" name="street" value="{{ old('street') }}" required>@error('street', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="barangay">Barangay</label><input class="form-field" id="barangay" name="barangay" value="{{ old('barangay') }}" required>@error('barangay', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="city">City</label><input class="form-field" id="city" name="city" value="{{ old('city') }}" required>@error('city', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="province">Province</label><input class="form-field" id="province" name="province" value="{{ old('province') }}" required>@error('province', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="zip_code">ZIP code</label><input class="form-field" id="zip_code" name="zip_code" value="{{ old('zip_code') }}" required>@error('zip_code', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                        </div>
                        <div class="mt-4 grid gap-4 md:grid-cols-3">
                            <div><label class="form-label" for="educational_attainment">Educational attainment</label><input class="form-field" id="educational_attainment" name="educational_attainment" value="{{ old('educational_attainment') }}" required>@error('educational_attainment', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="school_name">School</label><input class="form-field" id="school_name" name="school_name" value="{{ old('school_name') }}" required>@error('school_name', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="education_year_graduated">Year graduated</label><input class="form-field" id="education_year_graduated" name="education_year_graduated" type="number" min="1950" max="{{ now()->year }}" value="{{ old('education_year_graduated') }}" required>@error('education_year_graduated', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                        </div>
                    </section>

                    <section>
                        <div class="border-b border-slate-200 pb-3"><p class="text-xs font-black uppercase tracking-wide text-purple-700">Step 3</p><h2 class="mt-1 font-display text-xl font-bold">Previous MCARE training record</h2></div>
                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                            <div><label class="form-label" for="training_completion_year">Training completion year</label><input class="form-field" id="training_completion_year" name="training_completion_year" type="number" min="1950" max="{{ now()->year }}" value="{{ old('training_completion_year') }}" required>@error('training_completion_year', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="historical_batch_name">Old batch name/number <span class="font-normal text-slate-400">(if known)</span></label><input class="form-field" id="historical_batch_name" name="historical_batch_name" value="{{ old('historical_batch_name') }}">@error('historical_batch_name', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="training_schedule">Training schedule</label><select class="form-field" id="training_schedule" name="training_schedule" required><option value="">Select</option><option value="AM" @selected(old('training_schedule') === 'AM')>AM</option><option value="PM" @selected(old('training_schedule') === 'PM')>PM</option></select>@error('training_schedule', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="evidence_type">Record you will present</label><select class="form-field" id="evidence_type" name="evidence_type" required><option value="">Select</option><option value="certificate" @selected(old('evidence_type') === 'certificate')>Certificate of Completion (COTC)</option><option value="tor" @selected(old('evidence_type') === 'tor')>Training Record / TOR</option><option value="both" @selected(old('evidence_type') === 'both')>Both COTC and TOR</option></select>@error('evidence_type', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="certificate_number">COTC number <span class="font-normal text-slate-400">(if printed)</span></label><input class="form-field" id="certificate_number" name="certificate_number" value="{{ old('certificate_number') }}">@error('certificate_number', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="tor_reference">TOR/reference number <span class="font-normal text-slate-400">(if printed)</span></label><input class="form-field" id="tor_reference" name="tor_reference" value="{{ old('tor_reference') }}">@error('tor_reference', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                        </div>

                        <div class="mt-5 rounded-xl border border-dashed border-purple-300 bg-purple-50/60 p-4" data-claim-drop-zone>
                            <div class="flex flex-col items-center justify-center gap-2 py-3 text-center">
                                <span class="grid h-11 w-11 place-items-center rounded-full bg-white text-2xl font-light text-purple-700 shadow-sm">+</span>
                                <strong class="text-sm text-slate-900">Optional COTC/TOR preview</strong>
                                <p class="max-w-md text-xs leading-5 text-slate-600">Drag files here or choose up to two pages. Originals must still be presented on-site.</p>
                            </div>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <div><label class="form-label" for="evidence_document">Page 1</label><input class="block w-full rounded-lg border border-slate-200 bg-white p-2 text-xs" id="evidence_document" name="evidence_document" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" data-claim-file>@error('evidence_document', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                                <div><label class="form-label" for="evidence_document_page_2">Page 2</label><input class="block w-full rounded-lg border border-slate-200 bg-white p-2 text-xs" id="evidence_document_page_2" name="evidence_document_page_2" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" data-claim-file>@error('evidence_document_page_2', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            </div>
                            <p class="mt-3 text-center text-xs font-semibold text-purple-800" data-claim-file-status>No optional preview selected.</p>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                        <label class="flex items-start gap-3 text-sm leading-6 text-slate-700">
                            <input name="privacy_consent" type="checkbox" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-600" @checked(old('privacy_consent')) required>
                            <span>I authorize MCARE to use these details only for historical training-record, identity, and alumni eligibility verification. I understand that this form alone does not prove graduation.</span>
                        </label>
                        @error('privacy_consent', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror
                    </section>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ route('landing') }}" class="secondary-action justify-center">Cancel</a>
                        <button type="submit" class="primary-action justify-center" data-claim-submit>Submit alumni claim</button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <script>
        (() => {
            const zone = document.querySelector('[data-claim-drop-zone]');
            const inputs = [...document.querySelectorAll('[data-claim-file]')];
            const status = document.querySelector('[data-claim-file-status]');
            const form = document.querySelector('[data-alumni-claim-form]');
            const submit = document.querySelector('[data-claim-submit]');
            const updateStatus = () => {
                const names = inputs.flatMap(input => [...input.files].map(file => file.name));
                if (status) status.textContent = names.length ? names.join(' · ') : 'No optional preview selected.';
            };
            inputs.forEach(input => input.addEventListener('change', updateStatus));
            ['dragenter', 'dragover'].forEach(eventName => zone?.addEventListener(eventName, event => {
                event.preventDefault();
                zone.classList.add('border-purple-600', 'bg-purple-100');
            }));
            ['dragleave', 'drop'].forEach(eventName => zone?.addEventListener(eventName, event => {
                event.preventDefault();
                zone.classList.remove('border-purple-600', 'bg-purple-100');
            }));
            zone?.addEventListener('drop', event => {
                const files = [...event.dataTransfer.files].slice(0, 2);
                files.forEach((file, index) => {
                    if (!inputs[index]) return;
                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    inputs[index].files = transfer.files;
                });
                updateStatus();
            });
            form?.addEventListener('submit', () => {
                if (!submit) return;
                submit.disabled = true;
                submit.textContent = 'Submitting claim...';
            });
        })();
    </script>
</body>
</html>
