<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments | MCARE</title>
    <x-site-favicon />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="enrollment-page min-h-screen bg-[#f3f2f6] font-sans text-slate-900 antialiased">
    <div id="action-toast" class="fixed right-5 top-5 z-50 hidden max-w-sm rounded-lg border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold leading-6 text-amber-800">
        Too many actions. Please wait for the current request to finish.
    </div>

    <x-public-official-header
        masthead-aside="Caregiving NC II · Official payment"
        nav-label="Payments"
        :secondary-href="route('enrollment.create')"
        secondary-label="Enrollment"
        :secondary-compact-hide="false"
        :primary-href="route('login')"
        primary-label="Sign in"
    />

    <main class="enrollment-main mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
        <article class="enrollment-sheet">
            <header class="enrollment-intro">
                <p class="enrollment-kicker">Enrollment payment</p>
                <h1>Continue with your enrollment number</h1>
                <p class="enrollment-lede">Enter the enrollment number issued after you submitted the enrollment form. If it is already paid, this page will show that status. If it is not paid yet, the payment methods will appear.</p>
            </header>

            <div class="enrollment-form-body space-y-6">
                <form method="POST" action="{{ route('payments.lookup') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="form-label" for="enrollment_number">Enrollment number</label>
                        <input class="form-field" id="enrollment_number" name="enrollment_number" value="{{ old('enrollment_number', $submittedNumber) }}" placeholder="MCE-2026-XXXXXX" required>
                        @error('enrollment_number')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="primary-action">Look up payment</button>
                </form>

                @if (session('payment_notice'))
                    <p class="enrollment-notice enrollment-notice-ok">{{ session('payment_notice') }}</p>
                @endif

                @if ($errors->any())
                    <p class="enrollment-notice enrollment-notice-error">{{ $errors->first('payment') ?: $errors->first() }}</p>
                @endif

                @if ($lookedUp && ! $application)
                    <p class="enrollment-notice enrollment-notice-error" role="alert">That enrollment number was not found. Check the number from the payment page or your confirmation email and try again.</p>
                @endif

                @if ($application && $paymentCleared)
                    @include('enrollment.partials.enrollment-number')
                    @include('enrollment.partials.enrollment-summary')
                    <p class="enrollment-notice enrollment-notice-ok">This enrollment is already paid. MCARE has the required payment on record.</p>
                @elseif ($application)
                    @include('enrollment.partials.enrollment-number')
                    @include('enrollment.partials.enrollment-summary')
                    @include('enrollment.partials.payment-methods')
                @endif
            </div>
        </article>
    </main>

    <x-public-official-footer note="Use the enrollment number to continue payment later." />

    @include('enrollment.partials.payment-scripts')
</body>
</html>
