<?php

namespace App\Services;

use App\Mail\PaymentReceiptMail;
use App\Models\EnrollmentApplication;
use App\Models\PaymentTransaction;
use App\Notifications\PaymentVerifiedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EnrollmentPaymentLifecycle
{
    public function __construct(
        private readonly AdminOperationsNotifier $adminNotifier,
    ) {}

    public function handleVerifiedPayment(EnrollmentApplication $application): bool
    {
        $result = DB::transaction(function () use ($application): array {
            $locked = EnrollmentApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->hasEnrollmentPaymentClearance()) {
                return [
                    'cleared' => false,
                    'notify' => false,
                    'review_released' => false,
                    'application' => $locked,
                ];
            }

            $meta = $locked->payment_meta ?? [];
            $shouldNotify = blank(data_get($meta, 'enrollment_clearance_notified_at'));

            if ($shouldNotify) {
                data_set($meta, 'enrollment_clearance_notified_at', now()->toIso8601String());
            }

            if ($locked->status === EnrollmentApplication::STATUS_PROFILE_SUBMITTED) {
                $locked->status = EnrollmentApplication::STATUS_PRE_ENLISTMENT;
            }

            $reviewReleased = $locked->review_released_at === null;
            if ($reviewReleased) {
                $locked->review_released_at = now();
            }

            $locked->payment_meta = $meta;
            $locked->save();

            if ($locked->user && $locked->user->role === 'applicant') {
                $locked->user->forceFill([
                    'applicant_status' => $locked->status,
                ])->save();
            }

            return [
                'cleared' => true,
                'notify' => $shouldNotify,
                'review_released' => $reviewReleased,
                'application' => $locked,
            ];
        }, 3);

        /** @var EnrollmentApplication $verifiedApplication */
        $verifiedApplication = $result['application'];
        $verifiedApplication->loadMissing('user');

        if ($result['notify'] && $verifiedApplication->user) {
            try {
                $verifiedApplication->user->notifyNow(
                    new PaymentVerifiedNotification($verifiedApplication),
                );
            } catch (Throwable $exception) {
                // Payment verification remains authoritative even if the queue
                // or mail transport is temporarily unavailable.
                report($exception);
            }
        }

        if ($result['cleared']) {
            $this->sendOfficialReceipt($verifiedApplication);
        }

        if ($result['review_released']) {
            $program = $verifiedApplication->program ?: 'Training program';
            $applicant = trim($verifiedApplication->first_name.' '.$verifiedApplication->last_name);

            $this->adminNotifier->notify(
                title: 'Paid application ready for review',
                message: "{$applicant}'s {$program} application has a verified payment and is ready for document and account review.",
                url: route('admin.enrollments.show', $verifiedApplication),
                icon: 'badge-check',
                event: 'enrollment.review.released',
                context: [
                    'enrollment_application_id' => $verifiedApplication->getKey(),
                    'training_batch_id' => $verifiedApplication->training_batch_id,
                ],
            );
        }

        return $result['cleared'];
    }

    public function sendOfficialReceipt(EnrollmentApplication $application, bool $allowPending = false): void
    {
        $application->load(['user', 'paymentTransactions']);

        $transaction = $application->paymentTransactions
            ->where('status', PaymentTransaction::STATUS_VERIFIED)
            ->sortByDesc(fn (PaymentTransaction $item): int => $item->verified_at?->getTimestamp() ?? $item->id)
            ->first();

        if (! $transaction && $allowPending) {
            $transaction = $application->paymentTransactions
                ->where('status', PaymentTransaction::STATUS_PENDING)
                ->where('payment_channel', PaymentTransaction::CHANNEL_ONSITE)
                ->sortByDesc(fn (PaymentTransaction $item): int => $item->id)
                ->first();
        }

        if (! $transaction) {
            return;
        }

        $emailedIds = array_map(
            'intval',
            data_get($application->payment_meta, 'receipt_emailed_transaction_ids', []) ?: [],
        );

        if (in_array((int) $transaction->id, $emailedIds, true)) {
            return;
        }

        $email = $application->email ?: $application->user?->email;

        if (blank($email)) {
            return;
        }

        try {
            Mail::to($email)->send(new PaymentReceiptMail($application, $transaction));
        } catch (Throwable $exception) {
            report($exception);

            return;
        }

        $emailedIds[] = (int) $transaction->id;

        $application->forceFill([
            'payment_meta' => array_merge($application->payment_meta ?? [], [
                'receipt_emailed_transaction_ids' => array_values(array_unique($emailedIds)),
            ]),
        ])->save();
    }
}
