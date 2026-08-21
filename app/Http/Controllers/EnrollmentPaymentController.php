<?php

namespace App\Http\Controllers;

use App\Exceptions\PayMongoCheckoutException;
use App\Models\EnrollmentApplication;
use App\Models\PaymentAttempt;
use App\Services\PayMongoCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EnrollmentPaymentController extends Controller
{
    private const DEFAULT_DOWNPAYMENT = '2000.00';

    private const DEFAULT_DOWNPAYMENT_MINOR = 200000;

    public function __construct(
        private readonly PayMongoCheckoutService $payMongo,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $application = $this->applicationFor($request);

        if (! $application) {
            return redirect()
                ->route('enrollment.create')
                ->with('saved', 'Complete your enrollment registration before choosing a payment method.');
        }

        $this->expireStaleReceipt($application);
        $application->refresh()->load('batch');
        $activeAttempt = $application->paymentAttempts()
            ->where('provider', 'paymongo')
            ->where('status', PaymentAttempt::STATUS_PENDING)
            ->where('livemode', $this->payMongo->isLiveMode())
            ->latest()
            ->first();
        $paymongoModeConflict = $application->paymentAttempts()
            ->where('provider', 'paymongo')
            ->whereIn('status', [
                PaymentAttempt::STATUS_CREATING,
                PaymentAttempt::STATUS_PENDING,
            ])
            ->where('livemode', '!=', $this->payMongo->isLiveMode())
            ->exists();

        return view('enrollment.payment', [
            'application' => $application,
            'paymongoConfigured' => $this->payMongo->secretConfigured(),
            'paymongoWebhookConfigured' => $this->payMongo->webhookConfigured(),
            'paymongoReady' => $this->payMongo->ready(),
            'paymongoLiveMode' => $this->payMongo->isLiveMode(),
            'paymongoMethods' => $this->payMongo->enabledMethods(),
            'paymongoModeConflict' => $paymongoModeConflict,
            'activeCheckoutUrl' => $activeAttempt
                && $this->payMongo->isTrustedCheckoutUrl($activeAttempt->checkout_url)
                    ? $activeAttempt->checkout_url
                    : null,
        ]);
    }

    public function select(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'in:onsite,online'],
        ]);

        $application = $this->applicationFor($request);

        if (! $application) {
            return redirect()->route('enrollment.create');
        }

        $this->expireStaleReceipt($application);
        $application->refresh();

        // A confirmed payment is terminal; UI retries must never downgrade it.
        if ($application->payment_status === EnrollmentApplication::PAYMENT_PAID) {
            return redirect()
                ->route('payment.show')
                ->with('payment_notice', 'Your payment is already confirmed.');
        }

        if ($validated['payment_method'] === 'onsite') {
            if (! $this->prepareOnsitePayment($application)) {
                $application->refresh();

                if ($application->payment_status === EnrollmentApplication::PAYMENT_PAID) {
                    return redirect()
                        ->route('payment.show')
                        ->with('payment_notice', 'Your payment is already confirmed.');
                }

                return redirect()
                    ->route('payment.show')
                    ->withErrors([
                        'payment' => 'A PayMongo checkout is still active. Continue that checkout or contact MCARE before changing payment methods.',
                    ]);
            }

            return redirect()
                ->route('payment.receipt')
                ->with('payment_notice', 'Pay-on-site receipt created. Bring this reference to MCARE before it expires.');
        }

        if (! $this->payMongo->ready()) {
            return redirect()
                ->route('payment.show')
                ->withErrors([
                    'payment' => 'Online payment is temporarily unavailable while secure payment verification is being configured. You may choose Pay on site or try again later.',
                ]);
        }

        try {
            $checkoutUrl = $this->prepareOnlinePayment($application);
        } catch (PayMongoCheckoutException $exception) {
            // Log only operational identifiers; never log keys or PayMongo response bodies.
            Log::warning('PayMongo checkout creation failed.', [
                'application_id' => $application->getKey(),
                'retryable' => $exception->retryable,
                'response_status' => $exception->responseStatus,
            ]);

            return redirect()
                ->route('payment.show')
                ->withErrors([
                    'payment' => 'Secure checkout could not be started. No payment was recorded. Please wait a moment and try again.',
                ]);
        }

        if (! $checkoutUrl) {
            return redirect()
                ->route('payment.show')
                ->with('payment_notice', 'Your payment is already confirmed.');
        }

        return redirect()->away($checkoutUrl);
    }

    public function returned(Request $request): RedirectResponse
    {
        $application = $this->applicationFor($request);

        if (! $application) {
            return redirect()->route('enrollment.create');
        }

        $application->refresh();
        $notice = $application->payment_status === EnrollmentApplication::PAYMENT_PAID
            ? 'Payment confirmed. Your MCARE payment record is now updated.'
            : 'PayMongo returned you safely to MCARE. Confirmation is still pending and will update only after the signed gateway notification arrives.';

        return redirect()
            ->route('payment.show')
            ->with('payment_notice', $notice);
    }

    public function cancelled(Request $request): RedirectResponse
    {
        if (! $this->applicationFor($request)) {
            return redirect()->route('enrollment.create');
        }

        return redirect()
            ->route('payment.show')
            ->with('payment_notice', 'Checkout was closed. No payment was confirmed, and you may continue the same secure checkout when ready.');
    }

    public function status(Request $request): JsonResponse
    {
        $application = $this->applicationFor($request);

        abort_unless($application, 404);
        $application->refresh();

        return response()->json([
            'status' => $application->payment_status,
            'label' => $application->paymentStatusLabel(),
            'paid' => $application->payment_status === EnrollmentApplication::PAYMENT_PAID,
            'verified_at' => $application->payment_verified_at?->toIso8601String(),
        ]);
    }

    public function receipt(Request $request): View|RedirectResponse
    {
        $application = $this->applicationFor($request);

        if (! $application || ! $application->payment_receipt_number) {
            return redirect()
                ->route('payment.show')
                ->with('payment_notice', 'Choose Pay on site first to generate a receipt.');
        }

        $this->expireStaleReceipt($application);

        return view('enrollment.receipt', [
            'application' => $application->refresh()->load('batch'),
            'downloadMode' => false,
        ]);
    }

    public function downloadReceipt(Request $request): Response|RedirectResponse
    {
        $application = $this->applicationFor($request);

        if (! $application || ! $application->payment_receipt_number) {
            return redirect()->route('payment.show');
        }

        $this->expireStaleReceipt($application);
        $application->refresh()->load('batch');

        $html = view('enrollment.receipt', [
            'application' => $application,
            'downloadMode' => true,
        ])->render();

        $filename = 'mcare-receipt-'.$application->payment_receipt_number.'.html';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function applicationFor(Request $request): ?EnrollmentApplication
    {
        if ($request->user()) {
            return EnrollmentApplication::where('user_id', $request->user()->id)
                ->latest()
                ->first();
        }

        $applicationId = $request->session()->get('enrollment.payment_application_id');

        return is_numeric($applicationId)
            ? EnrollmentApplication::query()->whereKey((int) $applicationId)->first()
            : null;
    }

    private function prepareOnsitePayment(EnrollmentApplication $application): bool
    {
        return DB::transaction(function () use ($application): bool {
            $lockedApplication = EnrollmentApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedApplication->load('batch');

            if ($lockedApplication->payment_status === EnrollmentApplication::PAYMENT_PAID) {
                return false;
            }

            /*
             * Never clear gateway identifiers while PayMongo can still accept
             * money for an immutable attempt. This also covers an expired app
             * row whose provider Checkout Session remains pending.
             */
            $activeOnlineAttempt = PaymentAttempt::query()
                ->where('enrollment_application_id', $lockedApplication->getKey())
                ->where('provider', 'paymongo')
                ->whereIn('status', [
                    PaymentAttempt::STATUS_CREATING,
                    PaymentAttempt::STATUS_PENDING,
                ])
                ->lockForUpdate()
                ->exists();

            if ($activeOnlineAttempt) {
                return false;
            }

            $ticketNumber = $lockedApplication->payment_receipt_number ?: $this->uniqueReference('MCR');
            $refNumber = $lockedApplication->payment_reference ?: $this->uniqueReference('MCARE-SITE');
            $expiresAt = $lockedApplication->payment_receipt_expires_at ?: $this->defaultDeadlineFor($lockedApplication);

            $lockedApplication->forceFill([
                'payment_method' => 'onsite',
                'payment_status' => $lockedApplication->payment_status === EnrollmentApplication::PAYMENT_PAID ? EnrollmentApplication::PAYMENT_PAID : EnrollmentApplication::PAYMENT_ONSITE_PENDING,
                'payment_amount' => $lockedApplication->payment_amount ?: self::DEFAULT_DOWNPAYMENT,
                'payment_currency' => 'PHP',
                'payment_reference' => $refNumber,
                'payment_receipt_number' => $ticketNumber,
                'payment_receipt_expires_at' => $expiresAt,
                'payment_selected_at' => $lockedApplication->payment_selected_at ?: now(),
                'paymongo_checkout_reference' => null,
                'paymongo_checkout_url' => null,
                'payment_meta' => [
                    'channel' => 'on_site',
                    'issued_by' => 'MCARE Hub',
                    'note' => 'Receipt is for on-site cashier verification only.',
                    'batch' => $lockedApplication->batch?->name,
                    'batch_deadline' => $lockedApplication->batch?->enrollment_ends_at?->toDateTimeString(),
                ],
            ])->save();

            \App\Models\PaymentTransaction::firstOrCreate(
                [
                    'enrollment_application_id' => $lockedApplication->id,
                    'payment_channel' => \App\Models\PaymentTransaction::CHANNEL_ONSITE,
                    'transaction_type' => \App\Models\PaymentTransaction::TYPE_DOWNPAYMENT,
                    'ticket_number' => $ticketNumber,
                ],
                [
                    'user_id' => $lockedApplication->user_id,
                    'amount' => self::DEFAULT_DOWNPAYMENT,
                    'status' => \App\Models\PaymentTransaction::STATUS_PENDING,
                    'paid_at' => now(),
                    'notes' => 'On-site downpayment order (₱2,000.00) for cashier verification.',
                ]
            );

            return true;
        }, 3);
    }

    /**
     * Persist the idempotency key before contacting PayMongo. Concurrent
     * submissions then reuse one provider request instead of charging twice.
     */
    private function prepareOnlinePayment(EnrollmentApplication $application): ?string
    {
        $attempt = DB::transaction(function () use ($application): ?PaymentAttempt {
            $lockedApplication = EnrollmentApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedApplication->payment_status === EnrollmentApplication::PAYMENT_PAID) {
                return null;
            }

            $existing = PaymentAttempt::query()
                ->where('enrollment_application_id', $lockedApplication->getKey())
                ->where('provider', 'paymongo')
                ->whereIn('status', [
                    PaymentAttempt::STATUS_CREATING,
                    PaymentAttempt::STATUS_PENDING,
                ])
                ->latest()
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->livemode !== $this->payMongo->isLiveMode()) {
                    throw new PayMongoCheckoutException(
                        'An active PayMongo attempt belongs to a different mode.',
                    );
                }

                if (! is_array(data_get($existing->meta, 'checkout_payload'))) {
                    $payload = $this->payMongo->buildCheckoutPayload($existing, $lockedApplication);
                    $existing->forceFill([
                        'meta' => array_merge($existing->meta ?? [], [
                            'checkout_payload' => $payload,
                            'checkout_payload_sha256' => hash(
                                'sha256',
                                json_encode($payload, JSON_THROW_ON_ERROR),
                            ),
                        ]),
                    ])->save();
                }

                return $existing;
            }

            $createdAttempt = PaymentAttempt::create([
                'enrollment_application_id' => $lockedApplication->getKey(),
                'provider' => 'paymongo',
                'merchant_reference' => $this->uniqueReference('MCARE-ONLINE'),
                'idempotency_key' => (string) Str::uuid(),
                'amount_minor' => self::DEFAULT_DOWNPAYMENT_MINOR,
                'currency' => 'PHP',
                'status' => PaymentAttempt::STATUS_CREATING,
                'livemode' => $this->payMongo->isLiveMode(),
                'meta' => [
                    'created_for' => 'enrollment_downpayment',
                    'created_at' => now()->toIso8601String(),
                ],
            ]);

            $payload = $this->payMongo->buildCheckoutPayload($createdAttempt, $lockedApplication);
            $createdAttempt->forceFill([
                'meta' => array_merge($createdAttempt->meta ?? [], [
                    'checkout_payload' => $payload,
                    'checkout_payload_sha256' => hash(
                        'sha256',
                        json_encode($payload, JSON_THROW_ON_ERROR),
                    ),
                ]),
            ])->save();

            return $createdAttempt;
        }, 3);

        if (! $attempt) {
            return null;
        }

        if (
            $attempt->status === PaymentAttempt::STATUS_PENDING
            && $this->payMongo->isTrustedCheckoutUrl($attempt->checkout_url)
        ) {
            return $attempt->checkout_url;
        }

        try {
            $checkout = $this->payMongo->createCheckout($attempt);
        } catch (PayMongoCheckoutException $exception) {
            $failureOutcome = DB::transaction(function () use ($attempt, $exception): array {
                $lockedAttempt = PaymentAttempt::query()
                    ->whereKey($attempt->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                // A concurrent success/webhook always wins over this stale failure.
                if (
                    $lockedAttempt->status === PaymentAttempt::STATUS_PENDING
                    && $this->payMongo->isTrustedCheckoutUrl($lockedAttempt->checkout_url)
                ) {
                    return ['checkout_url' => $lockedAttempt->checkout_url];
                }

                if ($lockedAttempt->status === PaymentAttempt::STATUS_PAID) {
                    return ['paid' => true];
                }

                $lockedAttempt->forceFill([
                    // Unknown network/409/429/5xx outcomes retain the same key.
                    'status' => $exception->retryable
                        ? PaymentAttempt::STATUS_CREATING
                        : PaymentAttempt::STATUS_FAILED,
                    'meta' => array_merge($lockedAttempt->meta ?? [], [
                        'last_failure_at' => now()->toIso8601String(),
                        'last_failure_status' => $exception->responseStatus,
                        'retryable' => $exception->retryable,
                    ]),
                ])->save();

                return [];
            }, 3);

            if (isset($failureOutcome['checkout_url'])) {
                return $failureOutcome['checkout_url'];
            }

            if (isset($failureOutcome['paid'])) {
                return null;
            }

            throw $exception;
        }

        $stillPayable = DB::transaction(function () use ($application, $attempt, $checkout): bool {
            $lockedApplication = EnrollmentApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedAttempt = PaymentAttempt::query()
                ->whereKey($attempt->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedApplication->payment_status === EnrollmentApplication::PAYMENT_PAID) {
                if ($lockedAttempt->status !== PaymentAttempt::STATUS_PAID) {
                    $lockedAttempt->forceFill([
                        'status' => PaymentAttempt::STATUS_EXPIRED,
                        'expired_at' => now(),
                    ])->save();
                }

                return false;
            }

            $lockedAttempt->forceFill([
                'provider_checkout_id' => $checkout['checkout_id'],
                'checkout_url' => $checkout['checkout_url'],
                'status' => PaymentAttempt::STATUS_PENDING,
                'meta' => array_merge($lockedAttempt->meta ?? [], [
                    'checkout_created_at' => now()->toIso8601String(),
                ]),
            ])->save();

            $lockedApplication->forceFill([
                'payment_method' => 'online',
                'payment_status' => EnrollmentApplication::PAYMENT_ONLINE_PENDING,
                'payment_amount' => self::DEFAULT_DOWNPAYMENT,
                'payment_currency' => 'PHP',
                'payment_reference' => $lockedAttempt->merchant_reference,
                'payment_receipt_number' => null,
                'payment_receipt_expires_at' => null,
                'payment_selected_at' => now(),
                'paymongo_checkout_reference' => $checkout['checkout_id'],
                'paymongo_checkout_url' => $checkout['checkout_url'],
                'payment_meta' => [
                    'channel' => 'paymongo',
                    'gateway_verified' => false,
                    'payment_attempt_id' => $lockedAttempt->getKey(),
                    'accepted_methods' => data_get(
                        $lockedAttempt->meta,
                        'checkout_payload.data.attributes.payment_method_types',
                        [],
                    ),
                    'mode' => $checkout['livemode'] ? 'live' : 'test',
                    'batch' => $lockedApplication->batch?->name,
                    'batch_deadline' => $lockedApplication->batch?->enrollment_ends_at?->toDateTimeString(),
                    'payment_deadline' => $lockedApplication->batch?->enrollment_ends_at?->toDateTimeString(),
                ],
            ])->save();

            return true;
        }, 3);

        return $stillPayable ? $checkout['checkout_url'] : null;
    }

    private function expireStaleReceipt(EnrollmentApplication $application): void
    {
        DB::transaction(function () use ($application): void {
            $lockedApplication = EnrollmentApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->first();

            if (
                ! $lockedApplication
                || $lockedApplication->payment_status !== EnrollmentApplication::PAYMENT_ONSITE_PENDING
            ) {
                return;
            }

            $lockedApplication->load('batch');
            $deadline = $lockedApplication->effectivePaymentDeadline();

            if (! $deadline || $deadline->isFuture()) {
                return;
            }

            $lockedApplication->forceFill([
                'payment_status' => EnrollmentApplication::PAYMENT_EXPIRED,
            ])->save();
        }, 3);
    }

    private function uniqueReference(string $prefix): string
    {
        do {
            $reference = $prefix.'-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
        } while (
            EnrollmentApplication::where('payment_reference', $reference)->exists()
            || EnrollmentApplication::where('payment_receipt_number', $reference)->exists()
            || EnrollmentApplication::where('paymongo_checkout_reference', $reference)->exists()
            || PaymentAttempt::where('merchant_reference', $reference)->exists()
        );

        return $reference;
    }

    private function defaultDeadlineFor(EnrollmentApplication $application)
    {
        return $application->batch?->enrollment_ends_at ?: now()->addDays(7)->endOfDay();
    }
}
