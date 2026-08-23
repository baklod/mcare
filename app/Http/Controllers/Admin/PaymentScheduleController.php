<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\PaymentAttempt;
use App\Models\PaymentTransaction;
use App\Models\TrainingBatch;
use App\Services\EnrollmentPaymentLifecycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;

class PaymentScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'method' => ['nullable', Rule::in(['onsite', 'online'])],
            'status' => ['nullable', Rule::in(array_keys(EnrollmentApplication::paymentStatuses()))],
            'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $paymentsQuery = EnrollmentApplication::query()
            ->with(['batch', 'user', 'paymentVerifier', 'paymentTransactions.recordedByAdmin', 'paymentTransactions.verifier'])
            ->whereNotNull('payment_method')
            ->where('payment_status', '!=', EnrollmentApplication::PAYMENT_NOT_SELECTED)
            ->latest('payment_selected_at');

        if ($method = $filters['method'] ?? null) {
            $paymentsQuery->where('payment_method', $method);
        }

        if ($status = $filters['status'] ?? null) {
            $paymentsQuery->where('payment_status', $status);
        }

        if ($batchId = $filters['batch_id'] ?? null) {
            $paymentsQuery->where('training_batch_id', $batchId);
        }

        if ($schedule = $filters['schedule'] ?? null) {
            $paymentsQuery->where('schedule_preference', $schedule);
        }

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $paymentsQuery->where(function ($query) use ($search) {
                $query->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhere('payment_receipt_number', 'like', "%{$search}%")
                    ->orWhere('paymongo_checkout_reference', 'like', "%{$search}%")
                    ->orWhereHas('paymentTransactions', function ($tQuery) use ($search) {
                        $tQuery->where('or_number', 'like', "%{$search}%")
                            ->orWhere('ticket_number', 'like', "%{$search}%");
                    });
            });
        }

        $allApplications = EnrollmentApplication::query()
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email', 'total_program_fee', 'total_paid_amount', 'payment_status', 'training_batch_id']);

        $totalCollected = (float) PaymentTransaction::query()->where('status', PaymentTransaction::STATUS_VERIFIED)->sum('amount');
        $totalDirectPaid = (float) EnrollmentApplication::query()->where('payment_status', EnrollmentApplication::PAYMENT_PAID)->whereDoesntHave('paymentTransactions')->sum('payment_amount');

        return view('admin.payment-schedules.index', [
            'batches' => TrainingBatch::query()->orderByDesc('year')->orderBy('name')->get(),
            'filters' => $filters,
            'paymentStatuses' => EnrollmentApplication::paymentStatuses(),
            'payments' => $paymentsQuery->paginate(15)->withQueryString(),
            'allApplications' => $allApplications,
            'stats' => [
                'onsite' => EnrollmentApplication::query()->where('payment_method', 'onsite')->count(),
                'online' => EnrollmentApplication::query()->where('payment_method', 'online')->count(),
                'fully_paid' => EnrollmentApplication::query()->where('payment_status', EnrollmentApplication::PAYMENT_PAID)->count(),
                'partially_paid' => EnrollmentApplication::query()->where('payment_status', EnrollmentApplication::PAYMENT_PARTIALLY_PAID)->count(),
                'pending' => EnrollmentApplication::query()->whereIn('payment_status', [
                    EnrollmentApplication::PAYMENT_ONSITE_PENDING,
                    EnrollmentApplication::PAYMENT_ONLINE_PENDING,
                ])->count(),
                'pending_receipts' => PaymentTransaction::query()->where('status', PaymentTransaction::STATUS_PENDING)->count(),
                'pending_tickets' => PaymentTransaction::query()
                    ->where('status', PaymentTransaction::STATUS_PENDING)
                    ->whereNotNull('ticket_number')
                    ->count(),
                'total_collected' => $totalCollected + $totalDirectPaid,
            ],
        ]);
    }

    public function storeTransaction(
        Request $request,
        EnrollmentApplication $enrollmentApplication,
        EnrollmentPaymentLifecycle $paymentLifecycle,
    ): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:100000'],
            'or_number' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/',
                Rule::unique('payment_transactions', 'or_number'),
            ],
            'transaction_type' => ['required', Rule::in(array_keys(PaymentTransaction::types()))],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $enrolleeName = trim($enrollmentApplication->first_name.' '.$enrollmentApplication->last_name);

        DB::transaction(function () use ($request, $validated, $enrollmentApplication): void {
            $locked = EnrollmentApplication::query()->lockForUpdate()->findOrFail($enrollmentApplication->id);

            if ($locked->payment_status === EnrollmentApplication::PAYMENT_PAID) {
                throw ValidationException::withMessages([
                    'amount' => 'This enrollee is already fully paid.',
                ]);
            }

            $activeOnlineAttempt = PaymentAttempt::query()
                ->where('enrollment_application_id', $locked->id)
                ->where('provider', 'paymongo')
                ->whereIn('status', [PaymentAttempt::STATUS_CREATING, PaymentAttempt::STATUS_PENDING])
                ->exists();
            if ($activeOnlineAttempt) {
                throw ValidationException::withMessages([
                    'payment' => 'An online PayMongo checkout is still active for this enrollee.',
                ]);
            }

            $amount = round((float) $validated['amount'], 2);
            if ($amount > $locked->remainingBalance()) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment cannot exceed the remaining balance of PHP '.number_format($locked->remainingBalance(), 2).'.',
                ]);
            }

            $transaction = PaymentTransaction::create([
                'enrollment_application_id' => $locked->id,
                'user_id' => $locked->user_id,
                'recorded_by_admin_id' => $request->user()->id,
                'transaction_type' => $validated['transaction_type'],
                'payment_channel' => PaymentTransaction::CHANNEL_ONSITE,
                'amount' => $amount,
                'or_number' => $validated['or_number'],
                'status' => PaymentTransaction::STATUS_VERIFIED,
                'paid_at' => $validated['paid_at'],
                'verified_at' => now(),
                'verified_by_id' => $request->user()->id,
                'notes' => $validated['notes'] ?? null,
            ]);

            $locked->payment_method = 'onsite';
            $locked->payment_selected_at ??= now();

            $locked->payment_receipt_number = $validated['or_number'];
            $locked->payment_verified_by_id = $request->user()->id;
            $locked->payment_verified_at = now();
            $locked->recalculatePaymentStatus();

            AdminActivityLog::record($request->user(), 'payment.onsite_transaction.recorded', $locked, [
                'amount' => $validated['amount'],
                'or_number' => $validated['or_number'],
                'type' => $validated['transaction_type'],
                'new_total_paid' => $locked->total_paid_amount,
                'remaining_balance' => $locked->remainingBalance(),
            ]);
        });

        $paymentLifecycle->handleVerifiedPayment($enrollmentApplication->refresh());

        return back()->with('saved', "On-site payment of ₱".number_format((float) $validated['amount'], 2)." recorded for {$enrolleeName} (OR #{$validated['or_number']}).");
    }

    public function verifyTransaction(
        Request $request,
        PaymentTransaction $transaction,
        EnrollmentPaymentLifecycle $paymentLifecycle,
    ): RedirectResponse
    {
        $rules = [
            'action' => ['required', Rule::in(['verify', 'reject'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        // A generated ticket has no OR yet; the cashier supplies it when the admin verifies the ticket.
        if ($request->input('action') === 'verify' && $transaction->isOnsiteTicket()) {
            $rules['or_number'] = [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/',
                Rule::unique('payment_transactions', 'or_number')->ignore($transaction->id),
            ];
            $rules['paid_at'] = ['required', 'date', 'before_or_equal:today'];
        } else {
            $rules['or_number'] = [
                'nullable',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/',
                Rule::unique('payment_transactions', 'or_number')->ignore($transaction->id),
            ];
            $rules['paid_at'] = ['nullable', 'date', 'before_or_equal:today'];
        }

        $validated = $request->validate($rules);

        if ($transaction->status !== PaymentTransaction::STATUS_PENDING) {
            return back()->withErrors(['payment' => 'This payment request has already been processed.']);
        }

        $application = $transaction->enrollmentApplication;
        $enrolleeName = trim($application->first_name.' '.$application->last_name);

        $result = DB::transaction(function () use ($request, $validated, $transaction, $application): array {
            $lockedApp = EnrollmentApplication::query()->lockForUpdate()->findOrFail($application->id);
            $lockedTransaction = PaymentTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($lockedTransaction->status !== PaymentTransaction::STATUS_PENDING) {
                return ['error' => 'This payment request has already been processed.'];
            }

            if ($validated['action'] === 'verify') {
                $orNumber = $validated['or_number'] ?? $lockedTransaction->or_number;

                if ($lockedTransaction->isOnsiteTicket() && blank($orNumber)) {
                    return ['error' => 'Enter the official receipt number before verifying this ticket.'];
                }

                if ((float) $lockedTransaction->amount > $lockedApp->remainingBalance()) {
                    return ['error' => 'This transaction exceeds the enrollee\'s current remaining balance. Review the ledger before verifying it.'];
                }

                $lockedTransaction->update([
                    'status' => PaymentTransaction::STATUS_VERIFIED,
                    'recorded_by_admin_id' => $request->user()->id,
                    'or_number' => $orNumber,
                    'paid_at' => $validated['paid_at'] ?? ($lockedTransaction->paid_at ?: now()),
                    'verified_at' => now(),
                    'verified_by_id' => $request->user()->id,
                    'notes' => ($validated['notes'] ?? null) ?: $lockedTransaction->notes,
                ]);

                $lockedApp->payment_receipt_number = $lockedTransaction->or_number ?: $lockedApp->payment_receipt_number;
                $lockedApp->payment_verified_by_id = $request->user()->id;
                $lockedApp->payment_verified_at = now();
                $lockedApp->recalculatePaymentStatus();

                AdminActivityLog::record($request->user(), 'payment.transaction.verified', $lockedApp, [
                    'transaction_id' => $lockedTransaction->id,
                    'amount' => $lockedTransaction->amount,
                    'or_number' => $lockedTransaction->or_number,
                ]);
            } else {
                $lockedTransaction->update([
                    'status' => PaymentTransaction::STATUS_REJECTED,
                    'recorded_by_admin_id' => $request->user()->id,
                    'notes' => ($validated['notes'] ?? null) ?: 'Payment proof was not accepted by administration.',
                ]);

                $lockedApp->recalculatePaymentStatus();

                AdminActivityLog::record($request->user(), 'payment.transaction.rejected', $lockedApp, [
                    'transaction_id' => $lockedTransaction->id,
                    'reason' => $validated['notes'] ?? null,
                ]);
            }

            return ['ok' => true];
        }, 3);

        if (isset($result['error'])) {
            return back()->withErrors(['payment' => $result['error']]);
        }

        if ($validated['action'] === 'verify') {
            $paymentLifecycle->handleVerifiedPayment($application->refresh());
        }

        $actionVerb = $validated['action'] === 'verify' ? 'verified' : 'rejected';

        return back()->with('saved', "Payment transaction for {$enrolleeName} was {$actionVerb}.");
    }

    public function receiptProof(Request $request, PaymentTransaction $transaction): BinaryFileResponse
    {
        $path = $transaction->receipt_proof_path;
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $reference = $transaction->or_number ?: 'transaction-'.$transaction->id;
        $filename = 'receipt-'.$reference.($extension !== '' ? '.'.$extension : '');
        $fallbackFilename = str($filename)->ascii()->replaceMatches('/[^A-Za-z0-9._-]/', '-')->toString();

        AdminActivityLog::record($request->user(), 'payment.receipt_proof.viewed', $transaction, [
            'application_id' => $transaction->enrollment_application_id,
            'or_number' => $transaction->or_number,
        ]);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => Storage::disk('local')->mimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $filename, $fallbackFilename),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function update(Request $request, EnrollmentApplication $enrollmentApplication): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['verify_paid', 'verify_downpayment', 'return_pending', 'mark_expired'])],
            'payment_verification_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = DB::transaction(function () use ($request, $validated, $enrollmentApplication): array {
            $lockedApplication = EnrollmentApplication::query()
                ->whereKey($enrollmentApplication->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedApplication->payment_method) {
                return ['error' => 'This enrollee has not selected a payment method.'];
            }

            if ($lockedApplication->payment_status === EnrollmentApplication::PAYMENT_PAID) {
                return ['error' => 'This payment is already confirmed and cannot be changed from this queue.'];
            }

            if (in_array($validated['action'], ['verify_paid', 'verify_downpayment'], true)) {
                return ['error' => $lockedApplication->payment_method === 'online'
                    ? 'Online payments are confirmed only by PayMongo’s signed webhook. Admins cannot mark them paid manually.'
                    : 'Verify the actual ticket or receipt ledger entry with its official OR, or record a new on-site payment.'];
            }

            $verifiedAmount = (float) $lockedApplication->paymentTransactions()
                ->where('status', PaymentTransaction::STATUS_VERIFIED)
                ->sum('amount');
            if ($verifiedAmount > 0) {
                return ['error' => 'This enrollee already has verified ledger entries. Add or review transactions instead of overriding the derived payment status.'];
            }

            $beforeStatus = $lockedApplication->payment_status;
            $meta = array_merge($lockedApplication->payment_meta ?? [], [
                'last_verification_action' => $validated['action'],
                'last_verified_at' => now()->toIso8601String(),
                'last_verified_by' => $request->user()->id,
            ]);

            $newStatus = match ($validated['action']) {
                'mark_expired' => EnrollmentApplication::PAYMENT_EXPIRED,
                default => $lockedApplication->payment_method === 'onsite'
                    ? EnrollmentApplication::PAYMENT_ONSITE_PENDING
                    : EnrollmentApplication::PAYMENT_ONLINE_PENDING,
            };

            $lockedApplication->forceFill([
                'payment_status' => $newStatus,
                'payment_verified_by_id' => $request->user()->id,
                'payment_verified_at' => now(),
                'payment_verification_notes' => $validated['payment_verification_notes'] ?? null,
                'payment_meta' => $meta,
            ])->save();

            AdminActivityLog::record($request->user(), 'payment.verification.updated', $lockedApplication, [
                'before' => $beforeStatus,
                'after' => $lockedApplication->payment_status,
                'method' => $lockedApplication->payment_method,
            ]);

            return [
                'name' => trim($lockedApplication->first_name.' '.$lockedApplication->last_name),
            ];
        }, 3);

        if (isset($result['error'])) {
            return back()->withErrors(['payment' => $result['error']]);
        }

        return back()->with('saved', 'Payment verification updated for '.$result['name'].'.');
    }
}
