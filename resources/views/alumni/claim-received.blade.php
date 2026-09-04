<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumni claim received | MCARE</title>
    <x-dashboard-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="enrollment-page alumni-claim-page min-h-screen bg-[#f3f2f6] font-sans text-slate-900 antialiased">
    <x-public-official-header
        masthead-aside="Caregiving NC II · Official alumni claim"
        nav-label="Alumni claim received"
        :secondary-href="route('landing')"
        secondary-label="Public site"
        :primary-href="route('login')"
        primary-label="Sign in"
    />

    <main class="enrollment-main mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
        <article class="enrollment-sheet">
            <header class="enrollment-intro">
                <p class="enrollment-kicker">Claim submitted</p>
                <h1>Claim received</h1>
                <p class="enrollment-lede">Your alumni claim was recorded. Verify your email, then visit MCARE with a valid ID and your original COTC or TOR.</p>
            </header>
            <div class="enrollment-form-body space-y-6">
                <p class="enrollment-notice enrollment-notice-ok" role="status">
                    @if(filled($name))
                        <strong>{{ $name }}</strong>, your alumni claim was recorded.
                    @else
                        <strong>Claim received.</strong> Your alumni claim was recorded.
                    @endif
                    @if($verificationSent && filled($email))
                        A verification link was sent to <strong>{{ $email }}</strong>.
                    @elseif(! $verificationSent)
                        The claim was recorded, but the verification email could not be sent. Contact MCARE administration.
                    @endif
                </p>

                <ol class="alumni-process">
                    <li><span>1</span><span><strong>Verify your email.</strong> Open the secure link sent to your inbox before visiting the center.</span></li>
                    <li><span>2</span><span><strong>Visit MCARE.</strong> Bring a valid ID and your original COTC or TOR.</span></li>
                    <li><span>3</span><span><strong>Record verification.</strong> An administrator checks the physical documents and the MCARE archive.</span></li>
                    <li><span>4</span><span><strong>Alumni access.</strong> Once approved, your graduate account becomes available.</span></li>
                </ol>

                <p class="enrollment-notice enrollment-notice-amber">Email verification confirms mailbox ownership only. Alumni access is activated only after physical identity, COTC/TOR, and archive verification.</p>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('login') }}" class="primary-action">Go to sign in</a>
                    <a href="{{ route('landing') }}" class="secondary-action">Return to public site</a>
                </div>
            </div>
        </article>
    </main>

    <x-public-official-footer note="Official alumni claim for TESDA-accredited Caregiving NC II. On-site identity and archive verification precede graduate account access." />
</body>
</html>
