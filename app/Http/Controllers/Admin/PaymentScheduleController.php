<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
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
            ->with(['batch', 'user', 'paymentVerifier'])
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
                    ->orWhere('paymongo_checkout_reference', 'like', "%{$search}%");
            });
        }

        return view('admin.payment-schedules.index', [
            'batches' => TrainingBatch::query()->orderByDesc('year')->orderBy('name')->get(),
            'filters' => $filters,
            'paymentStatuses' => EnrollmentApplication::paymentStatuses(),
            'payments' => $paymentsQuery->paginate(15)->withQueryString(),
            'stats' => [
                'onsite' => EnrollmentApplication::query()->where('payment_method', 'onsite')->count(),
                'online' => EnrollmentApplication::query()->where('payment_method', 'online')->count(),
                'paid' => EnrollmentApplication::query()->where('payment_status', EnrollmentApplication::PAYMENT_PAID)->count(),
                'pending' => EnrollmentApplication::query()->whereIn('payment_status', [
                    EnrollmentApplication::PAYMENT_ONSITE_PENDING,
                    EnrollmentApplication::PAYMENT_ONLINE_PENDING,
                ])->count(),
            ],
        ]);
    }

    public function update(Request $request, EnrollmentApplication $enrollmentApplication): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['verify_paid', 'return_pending', 'mark_expired'])],
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

            // Recheck terminal state under the same lock used for the write.
            if ($lockedApplication->payment_status === EnrollmentApplication::PAYMENT_PAID) {
                return ['error' => 'This payment is already confirmed and cannot be changed from this queue.'];
            }

            if (
                $lockedApplication->payment_method === 'online'
                && $validated['action'] === 'verify_paid'
            ) {
                return ['error' => 'Online payments are confirmed only by PayMongo’s signed webhook. Admins cannot mark them paid manually.'];
            }

            $newStatus = match ($validated['action']) {
                'verify_paid' => EnrollmentApplication::PAYMENT_PAID,
                'mark_expired' => EnrollmentApplication::PAYMENT_EXPIRED,
                default => $lockedApplication->payment_method === 'onsite'
                    ? EnrollmentApplication::PAYMENT_ONSITE_PENDING
                    : EnrollmentApplication::PAYMENT_ONLINE_PENDING,
            };

            $beforeStatus = $lockedApplication->payment_status;
            $meta = array_merge($lockedApplication->payment_meta ?? [], [
                'last_verification_action' => $validated['action'],
                'last_verified_at' => now()->toIso8601String(),
                'last_verified_by' => $request->user()->id,
            ]);

            $lockedApplication->forceFill([
                'payment_status' => $newStatus,
                'payment_verified_by_id' => $request->user()->id,
                'payment_verified_at' => now(),
                'payment_verification_notes' => $validated['payment_verification_notes'] ?? null,
                'payment_meta' => $meta,
            ])->save();

            AdminActivityLog::record($request->user(), 'payment.verification.updated', $lockedApplication, [
                'before' => $beforeStatus,
                'after' => $newStatus,
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
