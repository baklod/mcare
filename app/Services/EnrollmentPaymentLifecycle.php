<?php

namespace App\Services;

use App\Models\EnrollmentApplication;
use App\Notifications\PaymentVerifiedNotification;
use Illuminate\Support\Facades\DB;
use Throwable;

class EnrollmentPaymentLifecycle
{
    public function handleVerifiedPayment(EnrollmentApplication $application): bool
    {
        $result = DB::transaction(function () use ($application): array {
            $locked = EnrollmentApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isDownpaymentSatisfied()) {
                return ['cleared' => false, 'notify' => false, 'application' => $locked];
            }

            $meta = $locked->payment_meta ?? [];
            $shouldNotify = blank(data_get($meta, 'enrollment_clearance_notified_at'));

            if ($shouldNotify) {
                data_set($meta, 'enrollment_clearance_notified_at', now()->toIso8601String());
            }

            if ($locked->status === EnrollmentApplication::STATUS_PROFILE_SUBMITTED) {
                $locked->status = EnrollmentApplication::STATUS_PRE_ENLISTMENT;
            }

            $locked->payment_meta = $meta;
            $locked->save();

            if ($locked->user && $locked->user->role === 'applicant') {
                $locked->user->forceFill([
                    'applicant_status' => $locked->status,
                ])->save();
            }

            return ['cleared' => true, 'notify' => $shouldNotify, 'application' => $locked];
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

        return $result['cleared'];
    }
}
