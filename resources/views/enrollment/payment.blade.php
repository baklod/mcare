<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Method | MCARE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-slate-900 antialiased">
    @php
        $fullName = trim($application->first_name.' '.$application->middle_name.' '.$application->last_name.' '.$application->extension_name);
        $amount = 'PHP '.number_format((float) $application->payment_amount, 2);
        $batch = $application->batch;
        $scheduleLabel = $batch?->scheduleLabelFor($application->schedule_preference) ?? 'Schedule to be confirmed by admin';
        $roomLabel = $batch?->roomFor($application->schedule_preference) ?: 'Room to be announced';
        $deadline = $application->effectivePaymentDeadline() ?: $batch?->enrollment_ends_at;
        $statusClasses = [
            'not_selected' => 'bg-slate-50 text-slate-700 ring-slate-100',
            'onsite_pending' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'online_pending' => 'bg-purple-50 text-purple-700 ring-purple-100',
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'expired' => 'bg-red-50 text-red-700 ring-red-100',
        ];
        $paymentMethodLabels = [
            'gcash' => 'GCash',
            'card' => 'Credit / debit card',
            'qrph' => 'QR Ph',
            'grab_pay' => 'GrabPay',
            'paymaya' => 'Maya',
            'maya' => 'Maya',
        ];
        $paymentConfirmed = $application->payment_status === 'paid';
        $onlineCheckoutActive = $application->payment_status === 'online_pending' && filled($activeCheckoutUrl);
    @endphp

    <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-72 bg-gradient-to-b from-purple-100 via-purple-50/70 to-white"></div>
    <div class="pointer-events-none fixed inset-x-0 bottom-0 -z-10 h-72 bg-gradient-to-t from-purple-100 via-purple-50/60 to-white"></div>

    <div id="action-toast" class="fixed right-5 top-5 z-50 hidden max-w-sm rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold leading-6 text-amber-800 shadow-xl shadow-amber-100">
        Too many actions. Please wait for the current request to finish.
    </div>

    <header class="border-b border-purple-100 bg-white/90 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl flex-col gap-5 px-6 py-5 sm:flex-row sm:items-center sm:justify-between lg:px-8">
            <a href="{{ route('landing') }}" class="flex items-center gap-4">
                <img src="{{ asset('assets/official-logo.png') }}" alt="Mission Care Training Center logo" class="h-16 w-16 rounded-2xl object-contain">
                <span>
                    <span class="block text-base font-bold text-slate-900">Mission Care Training Center</span>
                    <span class="block text-sm text-slate-500">Payment method</span>
                </span>
            </a>
            <a href="{{ route('enrollment.create') }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:border-purple-200 hover:text-purple-700">
                Back to enrollment
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-6 py-10 lg:px-8 lg:py-14">
        @if (session('payment_notice'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold leading-6 text-emerald-700">
                {{ session('payment_notice') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold leading-6 text-red-700">
                {{ $errors->first('payment') ?: 'Please choose a valid payment method.' }}
            </div>
        @endif

        <section class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_380px]">
            <div class="rounded-3xl border border-purple-100 bg-white p-7 shadow-xl shadow-purple-100/40 sm:p-10">
                <div class="inline-flex items-center gap-2 rounded-full bg-purple-50 px-4 py-2 text-sm font-semibold text-purple-700 ring-1 ring-purple-100">
                    Step 2 of enrollment
                </div>
                <h1 class="mt-7 max-w-4xl text-4xl font-bold leading-tight text-slate-900 sm:text-5xl">
                    Choose your payment method
                </h1>
                <p class="mt-5 max-w-3xl text-lg leading-8 text-slate-600">
                    Pay online through a PayMongo-ready checkout path or reserve your slot with a unique pay-on-site receipt.
                </p>
            </div>

            <aside class="rounded-3xl border border-slate-100 bg-slate-50 p-7 shadow-sm">
                <p class="text-sm font-bold uppercase text-purple-600">Applicant summary</p>
                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl bg-white p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase text-slate-500">Name</p>
                        <p class="mt-1 font-bold text-slate-900">{{ $fullName }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $application->email }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase text-slate-500">Program</p>
                        <p class="mt-1 font-bold text-slate-900">{{ $application->program }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $batch ? $batch->name.' '.$batch->year : 'Batch to be assigned' }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase text-slate-500">{{ $application->schedule_preference }} schedule</p>
                        <p class="mt-1 font-bold text-slate-900">{{ $scheduleLabel }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $roomLabel }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase text-slate-500">Downpayment</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ $amount }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase text-slate-500">Enrollment deadline</p>
                        <p class="mt-1 font-bold text-slate-900">{{ $deadline?->format('M d, Y g:i A') ?? 'To be announced' }}</p>
                    </div>
                    <span class="inline-flex rounded-full px-4 py-2 text-sm font-bold ring-1 {{ $statusClasses[$application->payment_status] ?? 'bg-slate-50 text-slate-700 ring-slate-100' }}">
                        {{ $application->paymentStatusLabel() }}
                    </span>
                </div>
            </aside>
        </section>

        <section class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <form method="POST" action="{{ route('payment.select') }}" data-single-action class="rounded-3xl border border-purple-100 bg-white p-6 shadow-xl shadow-purple-100/40 sm:p-8">
                @csrf
                <input type="hidden" name="payment_method" value="online">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold uppercase text-purple-600">Pay online</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">PayMongo secured checkout</h2>
                    </div>
                    <span class="rounded-full bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700 ring-1 ring-purple-100">
                        {{ $paymongoLiveMode ? 'Live checkout' : 'Test checkout' }}
                    </span>
                </div>

                <div class="mt-6 rounded-3xl border border-slate-100 bg-slate-50 p-5">
                    <div class="grid grid-cols-2 gap-3">
                        @foreach ($paymongoMethods as $method)
                            <div class="rounded-2xl border border-slate-100 bg-white px-4 py-3 text-sm font-bold text-slate-700">
                                {{ $paymentMethodLabels[$method] ?? str($method)->replace('_', ' ')->title() }}
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-5 rounded-2xl border border-purple-100 bg-white p-4">
                        <p class="text-xs font-bold uppercase text-slate-500">Secure checkout reference</p>
                        <p class="mt-2 break-all text-sm font-bold text-slate-900">{{ $application->paymongo_checkout_reference ?: 'Generated after choosing Pay online' }}</p>
                    </div>
                    <div class="mt-4 rounded-2xl border border-slate-100 bg-white p-4">
                        <p class="text-xs font-bold uppercase text-slate-500">Payment deadline</p>
                        <p class="mt-2 text-sm font-bold text-slate-900">{{ $deadline?->format('M d, Y g:i A') ?? 'Set by admin schedule' }}</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ $batch ? $batch->name.' '.$batch->year.' / '.$application->schedule_preference.' / '.$roomLabel : 'Batch schedule pending' }}</p>
                    </div>
                </div>

                <div class="mt-6 space-y-3 text-sm leading-6 text-slate-600">
                    <p class="font-semibold text-slate-700">Protected handoff: server-created checkout, retry-safe payment reference, and no card or wallet credentials stored in MCARE.</p>
                    @if (! $paymongoConfigured)
                        <p class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 font-semibold text-amber-800">
                            Online checkout is not configured yet. No payment will be sent until a valid server-side key is available.
                        </p>
                    @elseif (! $paymongoWebhookConfigured)
                        <p class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 font-semibold text-amber-800">
                            Checkout is paused until the separate PayMongo webhook signing secret is added. This prevents an unverified browser return from being treated as paid.
                        </p>
                    @elseif ($paymongoModeConflict)
                        <p class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 font-semibold text-amber-800">
                            A checkout from a different PayMongo mode is still active. Contact MCARE before starting another checkout.
                        </p>
                    @elseif (! $paymongoLiveMode)
                        <p class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 font-semibold text-sky-800">
                            Test mode is active. Use PayMongo test payment details only; no real funds will be collected.
                        </p>
                    @endif
                    @if ($application->payment_status === 'online_pending')
                        <p id="payment-confirmation-status" class="rounded-2xl border border-purple-200 bg-purple-50 px-4 py-3 font-semibold text-purple-800" aria-live="polite">
                            Waiting for PayMongo’s signed confirmation. This page checks securely in the background.
                        </p>
                    @endif
                </div>

                <button
                    type="submit"
                    data-action-button
                    @disabled(! $paymongoReady || $paymentConfirmed || $paymongoModeConflict)
                    class="mt-7 inline-flex w-full items-center justify-center rounded-full bg-purple-600 px-6 py-4 text-sm font-bold text-white shadow-lg shadow-purple-100 hover:bg-purple-700 disabled:cursor-not-allowed disabled:bg-slate-400 disabled:shadow-none"
                >
                    @if ($paymentConfirmed)
                        Payment confirmed
                    @elseif ($onlineCheckoutActive)
                        Continue secure checkout
                    @elseif ($paymongoLiveMode)
                        Open secure checkout
                    @else
                        Open test checkout
                    @endif
                </button>
            </form>

            <form method="POST" action="{{ route('payment.select') }}" data-single-action class="rounded-3xl border border-slate-100 bg-white p-6 shadow-xl shadow-slate-200/60 sm:p-8">
                @csrf
                <input type="hidden" name="payment_method" value="onsite">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold uppercase text-purple-600">Pay on site</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Generate cashier receipt</h2>
                    </div>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-amber-100">Receipt</span>
                </div>

                <div class="mt-6 rounded-3xl border border-slate-100 bg-slate-50 p-5">
                    <dl class="grid grid-cols-1 gap-4">
                        <div class="rounded-2xl bg-white p-4">
                            <dt class="text-xs font-bold uppercase text-slate-500">Receipt number</dt>
                            <dd class="mt-2 break-all text-sm font-bold text-slate-900">{{ $application->payment_receipt_number ?: 'Generated after choosing Pay on site' }}</dd>
                        </div>
                        <div class="rounded-2xl bg-white p-4">
                            <dt class="text-xs font-bold uppercase text-slate-500">Expiration</dt>
                            <dd class="mt-2 text-sm font-bold text-slate-900">{{ $deadline?->format('M d, Y g:i A') ?? 'Generated with receipt' }}</dd>
                        </div>
                        <div class="rounded-2xl bg-white p-4">
                            <dt class="text-xs font-bold uppercase text-slate-500">Class destination</dt>
                            <dd class="mt-2 text-sm font-bold text-slate-900">{{ $application->schedule_preference }} / {{ $scheduleLabel }} / {{ $roomLabel }}</dd>
                        </div>
                    </dl>
                </div>

                <p class="mt-6 text-sm font-semibold leading-6 text-slate-600">
                    The receipt includes the applicant name, Gmail, program, schedule, amount, unique identifier, and expiry date for on-site verification.
                </p>

                <div class="mt-7 grid gap-3 sm:grid-cols-2">
                    <button
                        type="submit"
                        data-action-button
                        @disabled($paymentConfirmed || $application->payment_status === 'online_pending')
                        class="inline-flex items-center justify-center rounded-full bg-slate-900 px-6 py-4 text-sm font-bold text-white shadow-lg shadow-slate-200 hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400 disabled:shadow-none"
                    >
                        Choose Pay on Site
                    </button>
                    @if ($application->payment_receipt_number)
                        <a href="{{ route('payment.receipt') }}" class="inline-flex items-center justify-center rounded-full border border-purple-200 bg-white px-6 py-4 text-sm font-bold text-purple-700 hover:bg-purple-50">
                            View Receipt
                        </a>
                    @endif
                </div>
            </form>
        </section>
    </main>

    <script>
        const toast = document.getElementById('action-toast');

        function showActionToast(message) {
            if (!toast) return;
            toast.textContent = message;
            toast.classList.remove('hidden');
            window.clearTimeout(window.mcareToastTimer);
            window.mcareToastTimer = window.setTimeout(() => toast.classList.add('hidden'), 2800);
        }

        document.querySelectorAll('[data-single-action]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (form.dataset.submitted === 'true') {
                    event.preventDefault();
                    showActionToast('Too many actions. Please wait for the current request to finish.');
                    return;
                }

                form.dataset.submitted = 'true';
                form.querySelectorAll('[data-action-button]').forEach((button) => {
                    button.disabled = true;
                    button.classList.add('cursor-not-allowed', 'opacity-70');
                    button.textContent = 'Processing securely...';
                });
            });
        });

        @if ($application->payment_status === 'online_pending')
            // The status endpoint reads only MCARE's server-side record; no
            // browser query parameter can turn a pending payment into paid.
            const paymentStatusUrl = @json(route('payment.status'));
            const confirmationStatus = document.getElementById('payment-confirmation-status');
            let paymentStatusChecks = 0;
            const paymentStatusTimer = window.setInterval(async () => {
                paymentStatusChecks += 1;

                try {
                    const response = await fetch(paymentStatusUrl, {
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        cache: 'no-store',
                    });

                    if (response.ok) {
                        const status = await response.json();

                        if (status.paid === true) {
                            window.clearInterval(paymentStatusTimer);
                            if (confirmationStatus) {
                                confirmationStatus.textContent = 'Payment confirmed securely. Updating your record...';
                            }
                            window.setTimeout(() => window.location.reload(), 500);
                            return;
                        }
                    }
                } catch (error) {
                    // A temporary network issue leaves the payment pending and
                    // the next bounded check can recover without user action.
                }

                if (paymentStatusChecks >= 20) {
                    window.clearInterval(paymentStatusTimer);
                    if (confirmationStatus) {
                        confirmationStatus.textContent = 'Confirmation is still pending. You can safely refresh this page later.';
                    }
                }
            }, 3000);
        @endif
    </script>
</body>
</html>
