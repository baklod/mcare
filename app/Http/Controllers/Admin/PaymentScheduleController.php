<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\PaymentTransaction;
use App\Models\TrainingBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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

    public function storeTransaction(Request $request, EnrollmentApplication $enrollmentApplication): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:100000'],
            'or_number' => ['required', 'string', 'max:100'],
            'transaction_type' => ['required', Rule::in(array_keys(PaymentTransaction::types()))],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $enrolleeName = trim($enrollmentApplication->first_name.' '.$enrollmentApplication->last_name);

        DB::transaction(function () use ($request, $validated, $enrollmentApplication): void {
            $locked = EnrollmentApplication::query()->lockForUpdate()->findOrFail($enrollmentApplication->id);

            $transaction = PaymentTransaction::create([
                'enrollment_application_id' => $locked->id,
                'user_id' => $locked->user_id,
                'recorded_by_admin_id' => $request->user()->id,
                'transaction_type' => $validated['transaction_type'],
                'payment_channel' => PaymentTransaction::CHANNEL_ONSITE,
                'amount' => $validated['amount'],
                'or_number' => $validated['or_number'],
                'status' => PaymentTransaction::STATUS_VERIFIED,
                'paid_at' => $validated['paid_at'],
                'verified_at' => now(),
                'verified_by_id' => $request->user()->id,
                'notes' => $validated['notes'] ?? null,
            ]);

            if (empty($locked->payment_method) || $locked->payment_method === 'not_selected') {
                $locked->payment_method = 'onsite';
                $locked->payment_selected_at = now();
            }

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

        return back()->with('saved', "On-site payment of ₱".number_format((float) $validated['amount'], 2)." recorded for {$enrolleeName} (OR #{$validated['or_number']}).");
    }

    public function verifyTransaction(Request $request, PaymentTransaction $transaction): RedirectResponse
    {
        $rules = [
            'action' => ['required', Rule::in(['verify', 'reject'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        // A generated ticket has no OR yet; the cashier supplies it when the admin verifies the ticket.
        if ($request->input('action') === 'verify' && $transaction->isOnsiteTicket()) {
            $rules['or_number'] = ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/'];
            $rules['paid_at'] = ['required', 'date', 'before_or_equal:today'];
        } else {
            $rules['or_number'] = ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/'];
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

        $actionVerb = $validated['action'] === 'verify' ? 'verified' : 'rejected';

        return back()->with('saved', "Payment transaction for {$enrolleeName} was {$actionVerb}.");
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

            if (
                $lockedApplication->payment_method === 'online'
                && in_array($validated['action'], ['verify_paid', 'verify_downpayment'], true)
            ) {
                return ['error' => 'Online payments are confirmed only by PayMongo’s signed webhook. Admins cannot mark them paid manually.'];
            }

            $beforeStatus = $lockedApplication->payment_status;
            $meta = array_merge($lockedApplication->payment_meta ?? [], [
                'last_verification_action' => $validated['action'],
                'last_verified_at' => now()->toIso8601String(),
                'last_verified_by' => $request->user()->id,
            ]);

            if ($validated['action'] === 'verify_downpayment') {
                $pendingTx = $lockedApplication->paymentTransactions()
                    ->where('status', PaymentTransaction::STATUS_PENDING)
                    ->first();

                $orNum = $lockedApplication->payment_receipt_number ?: ('OR-'.now()->format('Ymd').'-'.$lockedApplication->id);

                if ($pendingTx) {
                    $pendingTx->update([
                        'status' => PaymentTransaction::STATUS_VERIFIED,
                        'recorded_by_admin_id' => $request->user()->id,
                        'or_number' => $pendingTx->or_number ?: $orNum,
                        'verified_at' => now(),
                        'verified_by_id' => $request->user()->id,
                        'notes' => $validated['payment_verification_notes'] ?? $pendingTx->notes,
                    ]);
                } else {
                    PaymentTransaction::create([
                        'enrollment_application_id' => $lockedApplication->id,
                        'user_id' => $lockedApplication->user_id,
                        'recorded_by_admin_id' => $request->user()->id,
                        'transaction_type' => PaymentTransaction::TYPE_DOWNPAYMENT,
                        'payment_channel' => PaymentTransaction::CHANNEL_ONSITE,
                        'amount' => 2000.00,
                        'or_number' => $orNum,
                        'status' => PaymentTransaction::STATUS_VERIFIED,
                        'paid_at' => now(),
                        'verified_at' => now(),
                        'verified_by_id' => $request->user()->id,
                        'notes' => $validated['payment_verification_notes'] ?? 'On-site downpayment verified by administrator.',
                    ]);
                }

                $lockedApplication->payment_receipt_number = $orNum;
                $lockedApplication->payment_verified_by_id = $request->user()->id;
                $lockedApplication->payment_verified_at = now();
                $lockedApplication->payment_verification_notes = $validated['payment_verification_notes'] ?? null;
                $lockedApplication->payment_meta = $meta;
                $lockedApplication->recalculatePaymentStatus();
            } elseif ($validated['action'] === 'verify_paid') {
                $pendingTx = $lockedApplication->paymentTransactions()
                    ->where('status', PaymentTransaction::STATUS_PENDING)
                    ->first();

                $orNum = $lockedApplication->payment_receipt_number ?: ('OR-'.now()->format('Ymd').'-'.$lockedApplication->id);

                if ($pendingTx) {
                    $pendingTx->update([
                        'status' => PaymentTransaction::STATUS_VERIFIED,
                        'recorded_by_admin_id' => $request->user()->id,
                        'or_number' => $pendingTx->or_number ?: $orNum,
                        'verified_at' => now(),
                        'verified_by_id' => $request->user()->id,
                        'notes' => $validated['payment_verification_notes'] ?? $pendingTx->notes,
                    ]);
                }

                $fee = (float) ($lockedApplication->total_program_fee ?? 22000.00);
                $paidSoFar = (float) $lockedApplication->paymentTransactions()
                    ->where('status', PaymentTransaction::STATUS_VERIFIED)
                    ->sum('amount');
                $remainingToPay = max(0.0, $fee - $paidSoFar);

                if ($remainingToPay > 0) {
                    PaymentTransaction::create([
                        'enrollment_application_id' => $lockedApplication->id,
                        'user_id' => $lockedApplication->user_id,
                        'recorded_by_admin_id' => $request->user()->id,
                        'transaction_type' => $paidSoFar > 0 ? PaymentTransaction::TYPE_BALANCE_SETTLEMENT : PaymentTransaction::TYPE_FULL_PAYMENT,
                        'payment_channel' => PaymentTransaction::CHANNEL_ONSITE,
                        'amount' => $remainingToPay,
                        'or_number' => $orNum,
                        'status' => PaymentTransaction::STATUS_VERIFIED,
                        'paid_at' => now(),
                        'verified_at' => now(),
                        'verified_by_id' => $request->user()->id,
                        'notes' => $validated['payment_verification_notes'] ?? 'Full program tuition verified by administrator.',
                    ]);
                }

                $lockedApplication->payment_receipt_number = $orNum;
                $lockedApplication->payment_verified_by_id = $request->user()->id;
                $lockedApplication->payment_verified_at = now();
                $lockedApplication->payment_verification_notes = $validated['payment_verification_notes'] ?? null;
                $lockedApplication->payment_meta = $meta;
                $lockedApplication->recalculatePaymentStatus();
            } else {
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
            }

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
