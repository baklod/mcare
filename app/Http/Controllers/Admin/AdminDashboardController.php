<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $activeBatch = TrainingBatch::active();

        // These counts power the admin overview cards and keep the dashboard action-first.
        $statusCounts = EnrollmentApplication::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $paymentCounts = EnrollmentApplication::query()
            ->selectRaw('payment_status, count(*) as aggregate')
            ->groupBy('payment_status')
            ->pluck('aggregate', 'payment_status')
            ->all();

        $totalApplications = EnrollmentApplication::query()->count();
        $approvedPaid = EnrollmentApplication::query()
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->where('payment_status', EnrollmentApplication::PAYMENT_PAID)
            ->count();

        // Certificates are not generated yet, so this is the current eligibility signal.
        $certificatesReady = $approvedPaid;

        return view('admin.dashboard', [
            'actionQueue' => EnrollmentApplication::query()
                ->with(['batch', 'user'])
                ->whereIn('status', [
                    EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
                    EnrollmentApplication::STATUS_PRE_ENLISTMENT,
                ])
                ->latest()
                ->limit(6)
                ->get(),
            'activeBatch' => $activeBatch,
            'certificatesReady' => $certificatesReady,
            'documentsToVerify' => EnrollmentApplication::query()
                ->whereIn('status', [
                    EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
                    EnrollmentApplication::STATUS_PRE_ENLISTMENT,
                ])
                ->count(),
            'paymentCounts' => $paymentCounts,
            'paymentsToday' => EnrollmentApplication::query()
                ->whereDate('payment_selected_at', now()->toDateString())
                ->count(),
            'paymentQueue' => EnrollmentApplication::query()
                ->with(['batch', 'user'])
                ->whereIn('payment_status', [
                    EnrollmentApplication::PAYMENT_ONSITE_PENDING,
                    EnrollmentApplication::PAYMENT_ONLINE_PENDING,
                ])
                ->latest('payment_selected_at')
                ->limit(5)
                ->get(),
            'recentLogs' => AdminActivityLog::query()
                ->with('user')
                ->latest()
                ->limit(5)
                ->get(),
            'stats' => [
                'pending_applications' => ($statusCounts[EnrollmentApplication::STATUS_PROFILE_SUBMITTED] ?? 0)
                    + ($statusCounts[EnrollmentApplication::STATUS_PRE_ENLISTMENT] ?? 0),
                'approved' => $statusCounts[EnrollmentApplication::STATUS_APPROVED] ?? 0,
                'paid' => $paymentCounts[EnrollmentApplication::PAYMENT_PAID] ?? 0,
                'total_applications' => $totalApplications,
            ],
        ]);
    }
}
