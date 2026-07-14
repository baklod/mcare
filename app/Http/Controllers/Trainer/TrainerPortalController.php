<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Services\TraineeRosterCsv;
use App\Services\TrainerScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $batchId = $request->integer('batch_id') ?: null;
        $schedule = in_array($request->query('schedule'), ['AM', 'PM'], true) ? $request->query('schedule') : null;
        $trainees = $this->traineeRosterQuery($search, $batchId, $schedule)
            ->with(['batch', 'user', 'moduleProgress'])
            ->paginate(15)
            ->withQueryString();

        return view('trainer.trainees', [
            'search' => $search,
            'batchId' => $batchId,
            'schedule' => $schedule,
            'batches' => TrainingBatch::query()->orderByDesc('year')->orderBy('name')->get(),
            'trainees' => $trainees,
        ]);
    }

    public function exportTrainees(Request $request, TraineeRosterCsv $csv): StreamedResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
        ]);
        $trainees = $this->traineeRosterQuery(
            trim((string) ($validated['search'] ?? '')),
            isset($validated['batch_id']) ? (int) $validated['batch_id'] : null,
            $validated['schedule'] ?? null,
        )->with(['batch', 'moduleProgress'])->get();

        return $csv->download($trainees, 'mcare-trainer-trainee-summary-'.now()->format('Y-m-d').'.csv');
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
                'module_completions' => ModuleProgress::query()->where('status', 'completed')->count(),
            ],
        ]);
    }

    private function approvedTrainees()
    {
        return EnrollmentApplication::query()
            ->where('status', EnrollmentApplication::STATUS_APPROVED);
    }

    private function traineeRosterQuery(string $search, ?int $batchId, ?string $schedule)
    {
        return $this->approvedTrainees()
            ->when($batchId, fn ($query) => $query->where('training_batch_id', $batchId))
            ->when($schedule, fn ($query) => $query->where('schedule_preference', $schedule))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name');
    }
}
