<?php

namespace App\Http\Controllers;

use App\Models\EnrollmentApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EnrollmentPaymentController extends Controller
{
    private const DEFAULT_DOWNPAYMENT = '2000.00';

    public function show(Request $request): View|RedirectResponse
    {
        $application = $this->applicationFor($request);

        if (! $application) {
            return redirect()
                ->route('enrollment.create')
                ->with('saved', 'Complete your enrollment registration before choosing a payment method.');
        }

        $this->expireStaleReceipt($application);

        return view('enrollment.payment', [
            'application' => $application->refresh()->load('batch'),
            'paymongoConfigured' => filled(config('services.paymongo.secret_key')),
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

        if ($validated['payment_method'] === 'onsite') {
            $this->prepareOnsitePayment($application);

            return redirect()
                ->route('payment.receipt')
                ->with('payment_notice', 'Pay-on-site receipt created. Bring this reference to MCARE before it expires.');
        }

        $this->prepareOnlinePayment($application);

        return redirect()
            ->route('payment.show')
            ->with('payment_notice', 'Secure online payment reference prepared for PayMongo checkout.');
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
        return EnrollmentApplication::where('user_id', $request->user()->id)
            ->latest()
            ->first();
    }

    private function prepareOnsitePayment(EnrollmentApplication $application): void
    {
        if ($application->hasActiveOnsiteReceipt()) {
            return;
        }

        $application->forceFill([
            'payment_method' => 'onsite',
            'payment_status' => EnrollmentApplication::PAYMENT_ONSITE_PENDING,
            'payment_amount' => self::DEFAULT_DOWNPAYMENT,
            'payment_currency' => 'PHP',
            'payment_reference' => $this->uniqueReference('MCARE-SITE'),
            'payment_receipt_number' => $this->uniqueReference('MCR'),
            'payment_receipt_expires_at' => $this->defaultDeadlineFor($application),
            'payment_selected_at' => now(),
            'paymongo_checkout_reference' => null,
            'paymongo_checkout_url' => null,
            'payment_meta' => [
                'channel' => 'on_site',
                'issued_by' => 'MCARE Hub',
                'note' => 'Receipt is for on-site cashier verification only.',
                'batch' => $application->batch?->name,
                'batch_deadline' => $application->batch?->enrollment_ends_at?->toDateTimeString(),
            ],
        ])->save();
    }

    private function prepareOnlinePayment(EnrollmentApplication $application): void
    {
        $application->forceFill([
            'payment_method' => 'online',
            'payment_status' => EnrollmentApplication::PAYMENT_ONLINE_PENDING,
            'payment_amount' => self::DEFAULT_DOWNPAYMENT,
            'payment_currency' => 'PHP',
            'payment_reference' => $application->payment_reference ?: $this->uniqueReference('MCARE-ONLINE'),
            'payment_receipt_number' => null,
            'payment_receipt_expires_at' => null,
            'payment_selected_at' => now(),
            'paymongo_checkout_reference' => $application->paymongo_checkout_reference ?: $this->uniqueReference('PMG'),
            'paymongo_checkout_url' => null,
            'payment_meta' => [
                'channel' => 'paymongo',
                'accepted_methods' => ['gcash', 'card', 'grab_pay', 'maya'],
                'mode' => filled(config('services.paymongo.secret_key')) ? 'configured' : 'ui_ready',
                'batch' => $application->batch?->name,
                'batch_deadline' => $application->batch?->enrollment_ends_at?->toDateTimeString(),
                'payment_deadline' => $application->batch?->enrollment_ends_at?->toDateTimeString(),
            ],
        ])->save();
    }

    private function expireStaleReceipt(EnrollmentApplication $application): void
    {
        if ($application->payment_status !== EnrollmentApplication::PAYMENT_ONSITE_PENDING) {
            return;
        }

        $deadline = $application->effectivePaymentDeadline();

        if (! $deadline || $deadline->isFuture()) {
            return;
        }

        $application->forceFill([
            'payment_status' => EnrollmentApplication::PAYMENT_EXPIRED,
        ])->save();
    }

    private function uniqueReference(string $prefix): string
    {
        do {
            $reference = $prefix.'-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
        } while (
            EnrollmentApplication::where('payment_reference', $reference)->exists()
            || EnrollmentApplication::where('payment_receipt_number', $reference)->exists()
            || EnrollmentApplication::where('paymongo_checkout_reference', $reference)->exists()
        );

        return $reference;
    }

    private function defaultDeadlineFor(EnrollmentApplication $application)
    {
        return $application->batch?->enrollment_ends_at ?: now()->addDays(7)->endOfDay();
    }
}
