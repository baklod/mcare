<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $activeBatch = TrainingBatch::active();

        // These counts power the admin overview cards and keep the dashboard action-first.
        $statusCounts = EnrollmentApplication::query()
            ->releasedForReview()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $paymentCounts = EnrollmentApplication::query()
            ->selectRaw('payment_status, count(*) as aggregate')
            ->groupBy('payment_status')
            ->pluck('aggregate', 'payment_status')
            ->all();

        $totalApplications = EnrollmentApplication::query()->releasedForReview()->count();
        $approvedPaid = EnrollmentApplication::query()
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->where('payment_status', EnrollmentApplication::PAYMENT_PAID)
            ->count();

        // Certificates are not generated yet, so this is the current eligibility signal.
        $certificatesReady = $approvedPaid;

        return view('admin.dashboard', [
            'actionQueue' => EnrollmentApplication::query()
                ->releasedForReview()
                ->with(['batch', 'user'])
                ->whereIn('status', [
                    EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
                    EnrollmentApplication::STATUS_PRE_ENLISTMENT,
                ])
                ->latest()
                ->limit(3)
                ->get(),
            'activeBatch' => $activeBatch,
            'certificatesReady' => $certificatesReady,
            'documentsToVerify' => EnrollmentApplication::query()
                ->releasedForReview()
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
            'learningModules' => TrainingModule::query()
                ->with(['batch:id,name,year'])
                ->withCount([
                    'progressRecords as unlocked_trainees_count' => function ($progress): void {
                        $progress
                            ->whereNotNull('unlocked_at')
                            ->where('status', '!=', ModuleProgress::STATUS_LOCKED);
                    },
                ])
                ->orderByDesc('is_published')
                ->latest('published_at')
                ->latest('id')
                ->limit(5)
                ->get(),
            'recentLogs' => AdminActivityLog::query()
                ->with('user')
                ->latest()
                ->limit(5)
                ->get(),
            'trainingTrend' => $this->trainingProgressTrend(),
            'stats' => [
                'pending_applications' => ($statusCounts[EnrollmentApplication::STATUS_PROFILE_SUBMITTED] ?? 0)
                    + ($statusCounts[EnrollmentApplication::STATUS_PRE_ENLISTMENT] ?? 0),
                'approved' => $statusCounts[EnrollmentApplication::STATUS_APPROVED] ?? 0,
                'paid' => $paymentCounts[EnrollmentApplication::PAYMENT_PAID] ?? 0,
                'total_applications' => $totalApplications,
            ],
        ]);
    }

    /**
     * @return list<array{label: string, key: string, enrolled: int, passing: int}>
     */
    private function trainingProgressTrend(): array
    {
        $months = collect(range(5, -1))->map(
            fn (int $ago) => now()->copy()->startOfMonth()->subMonths($ago)
        );

        $rangeStart = $months->first()->copy()->startOfDay();
        $rangeEnd = $months->last()->copy()->endOfMonth()->endOfDay();

        $enrolledRows = EnrollmentApplication::query()
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->where('created_at', '>=', $rangeStart->copy()->subYear())
            ->get(['reviewed_at', 'learning_started_at', 'created_at']);

        $firstCompetent = ModuleProgress::query()
            ->where('competency_outcome', ModuleProgress::OUTCOME_COMPETENT)
            ->where('updated_at', '>=', $rangeStart)
            ->selectRaw('enrollment_application_id, MIN(COALESCE(evaluated_at, completed_at, updated_at)) as passed_at')
            ->groupBy('enrollment_application_id')
            ->pluck('passed_at', 'enrollment_application_id');

        $passedByApplication = [];
        foreach ($firstCompetent as $applicationId => $passedAt) {
            if (! $passedAt) {
                continue;
            }

            $passedByApplication[(int) $applicationId] = Carbon::parse($passedAt)->format('Y-m');
        }

        EnrollmentApplication::query()
            ->where('learning_status', EnrollmentApplication::LEARNING_GRADUATED)
            ->where('updated_at', '>=', $rangeStart)
            ->get(['id', 'learning_status_changed_at', 'updated_at'])
            ->each(function (EnrollmentApplication $application) use (&$passedByApplication): void {
                if (isset($passedByApplication[$application->id])) {
                    return;
                }

                $date = $application->learning_status_changed_at ?? $application->updated_at;
                if ($date) {
                    $passedByApplication[$application->id] = $date->format('Y-m');
                }
            });

        $monthKeys = $months->mapWithKeys(fn (Carbon $m) => [$m->format('Y-m') => 0])->all();

        $enrolledByMonth = $monthKeys;
        foreach ($enrolledRows as $row) {
            $date = $row->reviewed_at ?? $row->learning_started_at ?? $row->created_at;
            if (! $date) {
                continue;
            }
            $key = $date->format('Y-m');
            if (isset($enrolledByMonth[$key])) {
                $enrolledByMonth[$key]++;
            }
        }

        $passingByMonth = $monthKeys;
        foreach ($passedByApplication as $monthKey) {
            if (isset($passingByMonth[$monthKey])) {
                $passingByMonth[$monthKey]++;
            }
        }

        return $months->map(fn (Carbon $month) => [
            'label' => $month->format('M'),
            'key' => $month->format('Y-m'),
            'enrolled' => $enrolledByMonth[$month->format('Y-m')] ?? 0,
            'passing' => $passingByMonth[$month->format('Y-m')] ?? 0,
        ])->values()->all();
    }
}
