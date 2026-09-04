<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application status | MCARE</title>
    <x-dashboard-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="enrollment-page application-page application-status-page min-h-screen bg-[#f3f2f6] font-sans text-slate-900 antialiased">
    <x-public-official-header
        masthead-aside="Caregiving NC II · Official application"
        nav-label="Application status"
        :secondary-href="route('applications.create')"
        secondary-label="Apply"
        :primary-href="route('login')"
        primary-label="Sign in"
    />

    <main class="enrollment-main mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
        <article class="enrollment-sheet">
            <header class="enrollment-intro">
                <p class="enrollment-kicker">Applicant self-service</p>
                <h1>Check application status</h1>
                <p class="enrollment-lede">Enter the application number issued after you submitted the applications page. Enrollment opens only when the status is approved.</p>
            </header>
            <div class="enrollment-form-body space-y-6">
                <form method="POST" action="{{ route('applications.lookup') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="form-label" for="application_number">Application number</label>
                        <input class="form-field" id="application_number" name="application_number" value="{{ old('application_number', $submittedNumber) }}" placeholder="MCA-2026-XXXXXX" required>
                        @error('application_number')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="primary-action">Look up status</button>
                </form>

                @if ($lookedUp && ! $admission)
                    <p class="enrollment-notice enrollment-notice-error" role="alert">That application number was not found. Check the number from your confirmation email and try again.</p>
                @endif

                @if ($admission)
                    <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Application number</p>
                            <p class="mt-1 font-display text-2xl font-extrabold text-slate-950">{{ $admission->application_number }}</p>
                        </div>
                        <p>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Status</span>
                            <span class="mt-1 block text-lg font-bold text-slate-900">{{ $admission->statusLabel() }}</span>
                        </p>
                        <p class="text-sm text-slate-600">Submitted {{ $admission->created_at?->format('M d, Y g:i A') }} for {{ $admission->program }}.</p>

                        @if ($admission->isPending())
                            <p class="enrollment-notice enrollment-notice-amber">MCARE is still reviewing this application. Return here anytime with the same number.</p>
                        @elseif ($admission->isApproved())
                            <p class="enrollment-notice enrollment-notice-ok">This application is approved. Enter this number on the enrollment page to open the TESDA form.</p>
                            @if ($admission->enrollment)
                                <a href="{{ route('login') }}" class="primary-action">Sign in to continue enrollment</a>
                            @else
                                <a href="{{ $admission->enrollmentUrl() }}" class="primary-action">Continue to enrollment</a>
                            @endif
                        @else
                            <p class="enrollment-notice enrollment-notice-error">This application was not approved.@if(filled($admission->admin_notes)) {{ $admission->admin_notes }}@endif</p>
                            <a href="{{ route('applications.create') }}" class="secondary-action">Submit a new application</a>
                        @endif
                    </div>
                @endif
            </div>
        </article>
    </main>

    <x-public-official-footer />
</body>
</html>
