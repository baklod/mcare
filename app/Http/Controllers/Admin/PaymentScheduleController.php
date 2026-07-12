<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        if (! $enrollmentApplication->payment_method) {
            return back()->withErrors(['payment' => 'This enrollee has not selected a payment method.']);
        }

        $newStatus = match ($validated['action']) {
            'verify_paid' => EnrollmentApplication::PAYMENT_PAID,
            'mark_expired' => EnrollmentApplication::PAYMENT_EXPIRED,
            default => $enrollmentApplication->payment_method === 'onsite'
                ? EnrollmentApplication::PAYMENT_ONSITE_PENDING
                : EnrollmentApplication::PAYMENT_ONLINE_PENDING,
        };

        $beforeStatus = $enrollmentApplication->payment_status;
        $meta = array_merge($enrollmentApplication->payment_meta ?? [], [
            'last_verification_action' => $validated['action'],
            'last_verified_at' => now()->toIso8601String(),
            'last_verified_by' => $request->user()->id,
        ]);

        $enrollmentApplication->forceFill([
            'payment_status' => $newStatus,
            'payment_verified_by_id' => $request->user()->id,
            'payment_verified_at' => now(),
            'payment_verification_notes' => $validated['payment_verification_notes'] ?? null,
            'payment_meta' => $meta,
        ])->save();

        AdminActivityLog::record($request->user(), 'payment.verification.updated', $enrollmentApplication, [
            'before' => $beforeStatus,
            'after' => $newStatus,
            'method' => $enrollmentApplication->payment_method,
        ]);

        return back()->with('saved', 'Payment verification updated for '.$enrollmentApplication->first_name.' '.$enrollmentApplication->last_name.'.');
    }
}
