<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Historical Alumni Record | MCARE</title>
    <x-dashboard-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="enrollment-page alumni-claim-page min-h-screen bg-[#f3f2f6] font-sans text-slate-900 antialiased">
    @php($claimErrors = $errors->getBag('alumniClaim'))

    <x-public-official-header
        masthead-aside="Caregiving NC II · Official alumni claim"
        nav-label="Alumni claim"
        :secondary-href="route('landing')"
        secondary-label="Public site"
        :primary-href="route('login')"
        primary-label="Sign in"
    />

    <main class="enrollment-main mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
        <article class="enrollment-sheet">
            <header class="enrollment-intro">
                <p class="enrollment-kicker">For graduates before the MCARE website</p>
                <h1>Claim your historical alumni record</h1>
                <p class="enrollment-lede">This is not a new enrollment. It connects your previous TESDA-accredited MCARE training record to a verified online account after on-site document review.</p>
                <ol class="alumni-process">
                    <li><span>1</span><span><strong>Verify your email.</strong> MCARE sends a secure link after submission.</span></li>
                    <li><span>2</span><span><strong>Visit MCARE.</strong> Bring a valid ID and your original COTC or TOR.</span></li>
                    <li><span>3</span><span><strong>Record verification.</strong> An administrator checks the physical documents and the MCARE archive.</span></li>
                    <li><span>4</span><span><strong>Alumni access.</strong> Once approved, your graduate account becomes available.</span></li>
                </ol>
                <p class="enrollment-notice enrollment-notice-amber">Email verification confirms mailbox ownership only. Alumni access is activated only after physical identity, COTC/TOR, and archive verification.</p>
            </header>

            <div class="enrollment-form-body">
                @if($claimErrors->any())
                    <p class="enrollment-notice enrollment-notice-error" role="alert">Please review the highlighted alumni claim fields.</p>
                @endif

                <form
                    method="POST"
                    action="{{ route('alumni.claim.store') }}"
                    enctype="multipart/form-data"
                    class="enrollment-form space-y-10"
                    data-alumni-claim-form
                    data-address-regions-url="{{ route('enrollment.address.regions') }}"
                    data-address-provinces-url="{{ route('enrollment.address.provinces') }}"
                    data-address-cities-url="{{ route('enrollment.address.cities') }}"
                    data-address-barangays-url="{{ route('enrollment.address.barangays') }}"
                    data-address-region="{{ old('region') }}"
                    data-address-province="{{ old('province') }}"
                    data-address-city="{{ old('city') }}"
                    data-address-barangay="{{ old('barangay') }}"
                >
                    @csrf
                    <section>
                        <div class="enrollment-section-heading border-b border-slate-200 pb-3">
                            <p>Step 1</p>
                            <h2>Identity and contact details</h2>
                        </div>
                        <div class="mt-5 grid gap-4 md:grid-cols-3">
                            <div><label class="form-label" for="first_name">First name</label><input class="form-field" id="first_name" name="first_name" value="{{ old('first_name') }}" required>@error('first_name', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="middle_name">Middle name</label><input class="form-field" id="middle_name" name="middle_name" value="{{ old('middle_name') }}">@error('middle_name', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="last_name">Last name</label><input class="form-field" id="last_name" name="last_name" value="{{ old('last_name') }}" required>@error('last_name', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="birth_date">Birth date</label><input class="form-field" id="birth_date" name="birth_date" type="date" value="{{ old('birth_date') }}" required>@error('birth_date', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="gender">Gender</label><select class="form-field" id="gender" name="gender" required><option value="">Select</option><option value="Male" @selected(old('gender') === 'Male')>Male</option><option value="Female" @selected(old('gender') === 'Female')>Female</option></select>@error('gender', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="contact_number">Contact number</label><input class="form-field" id="contact_number" name="contact_number" value="{{ old('contact_number') }}" required>@error('contact_number', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                        </div>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="form-label" for="email">Email to verify</label>
                                <input class="form-field" id="email" name="email" type="email" value="{{ old('email') }}" required>
                                @error('email', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div></div>
                            <div>
                                <label class="form-label" for="password">Create password</label>
                                <div class="enrollment-password-field">
                                    <input class="form-field" id="password" name="password" type="password" autocomplete="new-password" required>
                                    <button type="button" class="enrollment-password-toggle" data-password-toggle="password" aria-label="Show password" title="Show password">
                                        <x-dashboard-icon name="eye" class="h-4 w-4" />
                                    </button>
                                </div>
                                <div class="mt-3 space-y-1.5 text-xs font-semibold">
                                    <p id="pw-length-check" class="flex items-center gap-2 text-slate-500"><span class="grid h-5 w-5 place-items-center rounded-full border border-slate-300 text-[10px]"> </span> At least 10 characters</p>
                                    <p id="pw-letter-number-check" class="flex items-center gap-2 text-slate-500"><span class="grid h-5 w-5 place-items-center rounded-full border border-slate-300 text-[10px]"> </span> Contains a number</p>
                                    <p id="pw-case-check" class="flex items-center gap-2 text-slate-500"><span class="grid h-5 w-5 place-items-center rounded-full border border-slate-300 text-[10px]"> </span> Contains upper and lowercase letters</p>
                                    <p id="pw-symbol-check" class="flex items-center gap-2 text-slate-500"><span class="grid h-5 w-5 place-items-center rounded-full border border-slate-300 text-[10px]"> </span> Contains a symbol</p>
                                </div>
                                <p class="mt-2 text-xs leading-5 text-slate-500">Use a unique passphrase you do not reuse on another website.</p>
                                @error('password', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="form-label" for="password_confirmation">Confirm password</label>
                                <div class="enrollment-password-field">
                                    <input class="form-field" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                                    <button type="button" class="enrollment-password-toggle" data-password-toggle="password_confirmation" aria-label="Show password confirmation" title="Show password">
                                        <x-dashboard-icon name="eye" class="h-4 w-4" />
                                    </button>
                                </div>
                                <p id="pw-match-check" class="mt-3 flex items-center gap-2 text-xs font-semibold text-slate-500"><span class="grid h-5 w-5 place-items-center rounded-full border border-slate-300 text-[10px]"> </span> Passwords match</p>
                                @error('password_confirmation', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </section>

                    <section>
                        <div class="enrollment-section-heading border-b border-slate-200 pb-3">
                            <p>Step 2</p>
                            <h2>Current address and education</h2>
                            <p class="mt-2 text-sm font-normal leading-6 text-slate-500">Choose region first. Province, city/municipality, and barangay then load from the official geographic list.</p>
                        </div>
                        <div class="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label class="form-label" for="region">Region</label>
                                <select class="form-field" id="region" name="region" required data-address-field="region">
                                    <option value="">Select region</option>
                                </select>
                                @error('region', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="form-label" for="province">Province</label>
                                <select class="form-field" id="province" name="province" required data-address-field="province">
                                    <option value="">Select region first</option>
                                </select>
                                @error('province', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="form-label" for="city">City/Municipality</label>
                                <select class="form-field" id="city" name="city" required data-address-field="city">
                                    <option value="">Select province first</option>
                                </select>
                                @error('city', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="form-label" for="barangay">Barangay</label>
                                <select class="form-field" id="barangay" name="barangay" required data-address-field="barangay">
                                    <option value="">Select city first</option>
                                </select>
                                @error('barangay', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="form-label" for="street">Number and street</label>
                                <input class="form-field" id="street" name="street" value="{{ old('street') }}" required>
                                <p class="mt-2 text-xs leading-5 text-slate-500">Enter only the house/building number, street, and zone.</p>
                                @error('street', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="form-label" for="zip_code">ZIP code</label>
                                <input class="form-field" id="zip_code" name="zip_code" value="{{ old('zip_code') }}" required>
                                @error('zip_code', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <p class="mt-3 text-sm font-semibold text-slate-500" role="status" aria-live="polite" data-address-lookup-status></p>
                        <div class="mt-4 grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="form-label" for="educational_attainment">Educational attainment</label>
                                <select class="form-field" id="educational_attainment" name="educational_attainment" required>
                                    <option value="">Select</option>
                                    @foreach (\App\Models\AdmissionApplication::educationalAttainmentOptions() as $option)
                                        <option value="{{ $option }}" @selected(old('educational_attainment') === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('educational_attainment', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div><label class="form-label" for="school_name">School</label><input class="form-field" id="school_name" name="school_name" value="{{ old('school_name') }}" required>@error('school_name', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="education_year_graduated">Year graduated</label><input class="form-field" id="education_year_graduated" name="education_year_graduated" type="number" min="1950" max="{{ now()->year }}" value="{{ old('education_year_graduated') }}" required>@error('education_year_graduated', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                        </div>
                    </section>

                    <section>
                        <div class="enrollment-section-heading border-b border-slate-200 pb-3">
                            <p>Step 3</p>
                            <h2>Previous MCARE training record</h2>
                        </div>
                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                            <div><label class="form-label" for="training_completion_year">Training completion year</label><input class="form-field" id="training_completion_year" name="training_completion_year" type="number" min="1950" max="{{ now()->year }}" value="{{ old('training_completion_year') }}" required>@error('training_completion_year', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="historical_batch_name">Old batch name/number <span class="font-normal text-slate-400">(if known)</span></label><input class="form-field" id="historical_batch_name" name="historical_batch_name" value="{{ old('historical_batch_name') }}">@error('historical_batch_name', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="training_schedule">Training schedule</label><select class="form-field" id="training_schedule" name="training_schedule" required><option value="">Select</option><option value="AM" @selected(old('training_schedule') === 'AM')>AM</option><option value="PM" @selected(old('training_schedule') === 'PM')>PM</option></select>@error('training_schedule', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="evidence_type">Record you will present</label><select class="form-field" id="evidence_type" name="evidence_type" required><option value="">Select</option><option value="certificate" @selected(old('evidence_type') === 'certificate')>Certificate of Completion (COTC)</option><option value="tor" @selected(old('evidence_type') === 'tor')>Training Record / TOR</option><option value="both" @selected(old('evidence_type') === 'both')>Both COTC and TOR</option></select>@error('evidence_type', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="certificate_number">COTC number <span class="font-normal text-slate-400">(if printed)</span></label><input class="form-field" id="certificate_number" name="certificate_number" value="{{ old('certificate_number') }}">@error('certificate_number', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label" for="tor_reference">TOR/reference number <span class="font-normal text-slate-400">(if printed)</span></label><input class="form-field" id="tor_reference" name="tor_reference" value="{{ old('tor_reference') }}">@error('tor_reference', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                        </div>

                        <div class="alumni-drop-zone mt-5" data-claim-drop-zone>
                            <p class="enrollment-kicker">Optional COTC/TOR preview</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Drag files here or choose up to two pages. Originals must still be presented on-site.</p>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <div><label class="form-label" for="evidence_document">Page 1</label><input class="form-field" id="evidence_document" name="evidence_document" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" data-claim-file>@error('evidence_document', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                                <div><label class="form-label" for="evidence_document_page_2">Page 2</label><input class="form-field" id="evidence_document_page_2" name="evidence_document_page_2" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" data-claim-file>@error('evidence_document_page_2', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror</div>
                            </div>
                            <p class="mt-3 text-xs font-semibold text-slate-600" data-claim-file-status>No optional preview selected.</p>
                        </div>
                    </section>

                    <section>
                        <label class="flex items-start gap-3 text-sm leading-6 text-slate-700">
                            <input id="privacy_consent" name="privacy_consent" type="checkbox" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-600" @checked(old('privacy_consent')) required>
                            <span>I authorize MCARE to use these details only for historical training-record, identity, and alumni eligibility verification. I understand that this form alone does not prove graduation.</span>
                        </label>
                        @error('privacy_consent', 'alumniClaim')<p class="form-error">{{ $message }}</p>@enderror
                    </section>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ route('landing') }}" class="secondary-action justify-center">Cancel</a>
                        <button type="submit" class="primary-action justify-center disabled:cursor-not-allowed disabled:opacity-50" data-claim-submit data-default-label="Submit alumni claim" @disabled(! old('privacy_consent')) aria-disabled="{{ old('privacy_consent') ? 'false' : 'true' }}">Submit alumni claim</button>
                    </div>
                </form>
            </div>
        </article>
    </main>

    <x-public-official-footer note="Official alumni claim for TESDA-accredited Caregiving NC II. On-site identity and archive verification precede graduate account access." />

    <script>
        (() => {
            const zone = document.querySelector('[data-claim-drop-zone]');
            const inputs = [...document.querySelectorAll('[data-claim-file]')];
            const status = document.querySelector('[data-claim-file-status]');
            const form = document.querySelector('[data-alumni-claim-form]');
            const submit = document.querySelector('[data-claim-submit]');
            const privacyConsent = document.getElementById('privacy_consent');
            const syncSubmitButton = () => {
                if (!submit) return;
                const ready = Boolean(privacyConsent?.checked);
                submit.disabled = !ready;
                submit.setAttribute('aria-disabled', ready ? 'false' : 'true');
            };
            privacyConsent?.addEventListener('change', syncSubmitButton);
            syncSubmitButton();
            const updateStatus = () => {
                const names = inputs.flatMap(input => [...input.files].map(file => file.name));
                if (status) status.textContent = names.length ? names.join(' · ') : 'No optional preview selected.';
            };
            inputs.forEach(input => input.addEventListener('change', updateStatus));
            ['dragenter', 'dragover'].forEach(eventName => zone?.addEventListener(eventName, event => {
                event.preventDefault();
                zone.classList.add('is-active');
            }));
            ['dragleave', 'drop'].forEach(eventName => zone?.addEventListener(eventName, event => {
                event.preventDefault();
                zone.classList.remove('is-active');
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
            const passwordInput = document.getElementById('password');
            const passwordConfirmationInput = document.getElementById('password_confirmation');
            const setCheckState = (elementId, isValid) => {
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
            };
            const passwordMeetsRules = (password) => password.length >= 10
                && /\d/.test(password)
                && /[a-z]/.test(password)
                && /[A-Z]/.test(password)
                && /[^A-Za-z0-9]/.test(password);
            const updatePasswordChecks = () => {
                const password = passwordInput?.value || '';
                const confirmation = passwordConfirmationInput?.value || '';
                setCheckState('pw-length-check', password.length >= 10);
                setCheckState('pw-letter-number-check', /\d/.test(password));
                setCheckState('pw-case-check', /[a-z]/.test(password) && /[A-Z]/.test(password));
                setCheckState('pw-symbol-check', /[^A-Za-z0-9]/.test(password));
                setCheckState('pw-match-check', password.length > 0 && password === confirmation);
                passwordInput?.setCustomValidity(
                    password.length > 0 && !passwordMeetsRules(password)
                        ? 'Use a stronger password that meets every requirement below.'
                        : ''
                );
                passwordConfirmationInput?.setCustomValidity(
                    confirmation.length > 0 && password !== confirmation
                        ? 'Passwords do not match.'
                        : ''
                );
            };
            passwordInput?.addEventListener('input', updatePasswordChecks);
            passwordConfirmationInput?.addEventListener('input', updatePasswordChecks);
            updatePasswordChecks();

            form?.addEventListener('submit', (event) => {
                if (!privacyConsent?.checked) {
                    event.preventDefault();
                    syncSubmitButton();
                    privacyConsent?.focus({ preventScroll: true });
                    privacyConsent?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }
                if (!submit) return;
                submit.disabled = true;
                submit.textContent = 'Submitting claim...';
            });
        })();
    </script>
</body>
</html>
