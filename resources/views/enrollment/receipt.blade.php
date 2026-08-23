<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCARE Receipt {{ $application->payment_receipt_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: #f8fafc;
            color: #0f172a;
            font-family: Arial, Helvetica, sans-serif;
            padding: 32px;
        }
        .shell { max-width: 860px; margin: 0 auto; }
        .actions { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
        .button {
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            background: #fff;
            color: #334155;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 18px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
        }
        .button-primary { background: #9333ea; color: #fff; border-color: #9333ea; }
        .receipt {
            overflow: hidden;
            border: 1px solid #e9d5ff;
            border-radius: 28px;
            background: #fff;
            box-shadow: 0 24px 70px rgba(148, 163, 184, .25);
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 1px solid #f1f5f9;
            background: linear-gradient(135deg, #faf5ff, #fff);
            padding: 30px;
        }
        .brand { display: flex; align-items: center; gap: 16px; }
        .brand img { width: 72px; height: 72px; object-fit: contain; border-radius: 18px; }
        .brand h1 { margin: 0; font-size: 24px; }
        .brand p, .meta p { margin: 4px 0 0; color: #64748b; font-size: 13px; }
        .meta { text-align: right; }
        .meta strong { display: block; color: #581c87; font-size: 18px; }
        .content { padding: 30px; }
        .notice {
            border: 1px solid #fde68a;
            border-radius: 18px;
            background: #fffbeb;
            color: #92400e;
            padding: 16px 18px;
            font-size: 14px;
            line-height: 1.6;
            font-weight: 700;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-top: 22px;
        }
        .field {
            border: 1px solid #f1f5f9;
            border-radius: 18px;
            background: #f8fafc;
            padding: 16px;
        }
        .field.full { grid-column: 1 / -1; }
        .field span {
            display: block;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .field strong {
            display: block;
            margin-top: 7px;
            color: #0f172a;
            font-size: 15px;
            word-break: break-word;
        }
        .amount {
            margin-top: 24px;
            border-radius: 22px;
            background: #581c87;
            color: #fff;
            padding: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }
        .amount span { display: block; color: #e9d5ff; font-size: 13px; font-weight: 700; }
        .amount strong { display: block; margin-top: 4px; font-size: 30px; }
        .footer {
            border-top: 1px solid #f1f5f9;
            color: #64748b;
            font-size: 12px;
            line-height: 1.7;
            padding: 22px 30px 30px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .actions { display: none; }
            .receipt { box-shadow: none; border-radius: 0; }
        }
        @media (max-width: 700px) {
            body { padding: 18px; }
            .header, .amount { align-items: flex-start; flex-direction: column; }
            .meta { text-align: left; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    @php
        $fullName = collect([
            $application->first_name,
            $application->middle_name,
            $application->last_name,
            $application->extension_name,
        ])->filter(fn ($part) => filled($part))->join(' ');
        $amount = 'PHP '.number_format((float) $application->payment_amount, 2);
        $batch = $application->batch;
        $scheduleLabel = $batch?->scheduleLabelFor($application->schedule_preference) ?? 'Schedule to be confirmed by admin';
        $roomLabel = $batch?->roomFor($application->schedule_preference) ?: 'Room to be announced';
        $deadline = $application->effectivePaymentDeadline() ?: $batch?->enrollment_ends_at;
        $paymentCleared = $application->hasEnrollmentPaymentClearance();
        $logoPath = public_path('assets/official-logo.png');
        $logoSource = file_exists($logoPath)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
            : asset('assets/official-logo.png');
    @endphp

    <main class="shell">
        @unless ($downloadMode)
            <div class="actions">
                <a class="button" href="{{ route('payment.show') }}">Back to payment</a>
                <span>
                    <a class="button" href="{{ route('payment.receipt.download') }}">Download receipt</a>
                    <button class="button button-primary" type="button" onclick="window.print()">Print / Save PDF</button>
                </span>
            </div>

            @unless ($paymentCleared)
                <div id="payment-confirmation-status" class="notice" role="status" aria-live="polite" style="margin-bottom: 18px;">
                    Waiting for cashier verification. Keep this page open and MCARE will return you to the landing page automatically after the required payment is verified.
                </div>
            @endunless
        @endunless

        <section class="receipt">
            <div class="header">
                <div class="brand">
                    <img src="{{ $logoSource }}" alt="Mission Care logo">
                    <div>
                        <h1>Mission Care Training Center</h1>
                        <p>Caregiving NC II pay-on-site receipt</p>
                    </div>
                </div>
                <div class="meta">
                    <strong>{{ $application->payment_receipt_number }}</strong>
                    <p>Issued {{ $application->payment_selected_at?->format('M d, Y g:i A') ?? now()->format('M d, Y g:i A') }}</p>
                </div>
            </div>

            <div class="content">
                <div class="notice">
                    This receipt is unique to the applicant below and expires on {{ $deadline?->format('M d, Y g:i A') ?? 'the listed expiration date' }}. Bring this receipt to MCARE for cashier verification.
                </div>

                <div class="grid">
                    <div class="field">
                        <span>Trainee name</span>
                        <strong>{{ $fullName }}</strong>
                    </div>
                    <div class="field">
                        <span>Email account</span>
                        <strong>{{ $application->email }}</strong>
                    </div>
                    <div class="field">
                        <span>Contact number</span>
                        <strong>{{ $application->contact_number }}</strong>
                    </div>
                    <div class="field">
                        <span>Program</span>
                        <strong>{{ $application->program }}</strong>
                    </div>
                    <div class="field">
                        <span>Training Batch</span>
                        <strong>{{ $batch ? $batch->name.' '.$batch->year : 'Batch to be assigned' }}</strong>
                    </div>
                    <div class="field">
                        <span>Schedule & Room</span>
                        <strong>{{ $application->schedule_preference }} · {{ $roomLabel }}</strong>
                    </div>
                    <div class="field">
                        <span>Total Program Tuition</span>
                        <strong style="color: #581c87;">PHP {{ number_format((float) ($application->total_program_fee ?? 22000.00), 2) }}</strong>
                    </div>
                    <div class="field">
                        <span>Remaining Balance</span>
                        <strong style="color: #b45309;">PHP {{ number_format($application->remainingBalance(), 2) }}</strong>
                    </div>
                    <div class="field full">
                        <span>Official Payment Reference</span>
                        <strong style="font-family: monospace; font-size: 16px;">{{ $application->payment_reference ?: $application->payment_receipt_number }}</strong>
                    </div>
                </div>

                <div class="amount">
                    <div>
                        <span>Amount for Cashier Verification</span>
                        <strong>{{ $amount }}</strong>
                    </div>
                    <div>
                        <span>Payment Status</span>
                        <strong style="font-size: 20px;">{{ $application->paymentStatusLabel() }}</strong>
                    </div>
                </div>

                <div style="margin-top: 24px; border: 1px dashed #cbd5e1; border-radius: 18px; padding: 18px 24px; background: #fafafa; display: flex; justify-content: space-between; align-items: flex-end; gap: 20px;">
                    <div>
                        <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em;">Cashier / Finance Desk Use</span>
                        <p style="margin: 4px 0 0; font-size: 12px; color: #475569;">Verified by: ________________________</p>
                        <p style="margin: 4px 0 0; font-size: 12px; color: #475569;">Official Receipt (OR) #: ________________</p>
                    </div>
                    <div style="text-align: right;">
                        <p style="margin: 0; font-size: 12px; color: #475569;">Date: ______________</p>
                        <p style="margin: 4px 0 0; font-size: 11px; color: #94a3b8;">Official MCARE Cashier Stamp</p>
                    </div>
                </div>
            </div>

            <div class="footer">
                Receipt identifier and expiration date are generated by MCARE Hub. Enrollment schedule and final acceptance remain subject to admin verification and current batch availability.
            </div>
        </section>
    </main>

    @if (! $downloadMode && ! $paymentCleared)
        <script>
            const paymentStatusUrl = @json(route('payment.status'));
            const paymentCompleteUrl = @json(route('payment.complete'));
            const confirmationStatus = document.getElementById('payment-confirmation-status');
            let paymentStatusChecks = 0;

            window.setInterval(async () => {
                if (document.hidden) return;
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
                    if (!response.ok) return;

                    const status = await response.json();
                    if (status.payment_verified === true) {
                        if (confirmationStatus) {
                            confirmationStatus.textContent = 'Payment verified successfully. Returning you to MCARE while your account awaits approval...';
                        }
                        window.setTimeout(() => window.location.assign(status.completion_url || paymentCompleteUrl), 500);
                        return;
                    }
                } catch (error) {
                    // Keep waiting; email provides the fallback when the phone
                    // temporarily loses its data connection.
                }

                if (paymentStatusChecks >= 15 && confirmationStatus) {
                    confirmationStatus.textContent = 'Still waiting for cashier verification. Checking continues automatically; you may also close this page and wait for the MCARE email.';
                }
            }, 4000);
        </script>
    @endif
</body>
</html>
