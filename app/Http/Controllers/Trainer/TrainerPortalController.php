<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Services\TrainerScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class TrainerPortalController extends Controller
{
    public function trainings(Request $request): View
    {
        return view('trainer.trainings', [
            'batches' => TrainingBatch::query()
                ->withCount(['applications', 'modules'])
                ->orderByDesc('is_active')
                ->orderByDesc('year')
                ->get(),
        ]);
    }

    public function trainees(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $trainees = EnrollmentApplication::query()
            ->with(['batch', 'user', 'moduleProgress'])
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->paginate(15)
            ->withQueryString();

        return view('trainer.trainees', compact('search', 'trainees'));
    }

    public function sessions(Request $request, TrainerScheduleService $scheduleService): View
    {
        $validated = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $activeBatch = TrainingBatch::active();
        $defaultMonth = $activeBatch?->training_starts_at?->format('Y-m') ?? now()->format('Y-m');
        $month = Carbon::createFromFormat('Y-m', $validated['month'] ?? $defaultMonth)->startOfMonth();
        $sessions = $activeBatch ? $scheduleService->month($activeBatch, $month) : collect();

        return view('trainer.sessions', [
            'activeBatch' => $activeBatch,
            'month' => $month,
            'sessions' => $sessions,
            'sessionsByDate' => $sessions->groupBy('date_key'),
        ]);
    }

    public function assessments(): View
    {
        return view('trainer.assessments', [
            'trainees' => $this->approvedTrainees()->with(['batch', 'moduleProgress'])->get(),
            'publishedModules' => TrainingModule::query()->where('is_published', true)->count(),
        ]);
    }

    public function resources(Request $request): View
    {
        return view('trainer.resources', [
            'batches' => TrainingBatch::query()->orderByDesc('is_active')->orderByDesc('year')->get(),
            'modules' => TrainingModule::query()
                ->with(['batch', 'targetTrainee', 'progressRecords.application'])
                ->where('trainer_id', $request->user()->id)
                ->latest('published_at')
                ->get(),
            'trainees' => $this->approvedTrainees()->with('batch')->get(),
        ]);
    }

    public function certificates(): View
    {
        return view('trainer.certificates', [
            'trainees' => $this->approvedTrainees()
                ->with('batch')
                ->orderBy('last_name')
                ->get(),
        ]);
    }

    public function reports(): View
    {
        $activeBatch = TrainingBatch::active();
        $trainees = $this->approvedTrainees()->get();

        return view('trainer.reports', [
            'activeBatch' => $activeBatch,
            'stats' => [
                'trainees' => $trainees->count(),
                'am' => $trainees->where('schedule_preference', 'AM')->count(),
                'pm' => $trainees->where('schedule_preference', 'PM')->count(),
                'modules' => TrainingModule::query()->where('is_published', true)->count(),
                'paid' => $trainees->where('payment_status', EnrollmentApplication::PAYMENT_PAID)->count(),
                'module_completions' => \App\Models\ModuleProgress::query()->where('status', 'completed')->count(),
            ],
        ]);
    }

    private function approvedTrainees()
    {
        return EnrollmentApplication::query()
            ->where('status', EnrollmentApplication::STATUS_APPROVED);
    }
}
