<?php

namespace App\Services;

use App\Models\EnrollmentApplication;
use App\Notifications\PaymentVerifiedNotification;
use Illuminate\Support\Facades\DB;
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
                $verifiedApplication->user->notify(
                    new PaymentVerifiedNotification($verifiedApplication),
                );
            } catch (Throwable $exception) {
                // Payment verification remains authoritative even if the queue
                // or mail transport is temporarily unavailable.
                report($exception);
            }
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
}
