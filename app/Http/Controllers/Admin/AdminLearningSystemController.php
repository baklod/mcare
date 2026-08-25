<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\User;
use App\Rules\TrainingModuleFileType;
use App\Services\CompletionEligibilityService;
use App\Services\RollingModuleReleaseService;
use App\Services\TraineeRosterCsv;
use App\Support\TrainingModuleFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminLearningSystemController extends Controller
{
    public function trainees(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
            'learning_status' => ['nullable', Rule::in(array_keys(EnrollmentApplication::learningStatuses()))],
            'training_state' => ['nullable', Rule::in(['not_started', 'in_progress', 'completed'])],
            'joined_from' => ['nullable', 'date'],
            'joined_to' => ['nullable', 'date', 'after_or_equal:joined_from'],
        ]);

        $query = $this->filteredTrainees($filters)->with(['batch', 'moduleProgress', 'user']);
        $trainees = $query->paginate(20)->withQueryString();

        // Build one compact dashboard summary per learner from already eager-loaded
        // payment, module, and batch data. Assessment results are deliberately not
        // invented here because the assessment-recording phase is not implemented yet.
        $traineeSummaries = $trainees->getCollection()->mapWithKeys(function (EnrollmentApplication $trainee) {
            // Use the exact same audience query as the trainee portal so an
            // individual, batch, or future global module cannot drift between dashboards.
            $availableModules = TrainingModule::query()
                ->availableTo($trainee)
                ->orderBy('position')
                ->get();
            $availableModuleIds = $availableModules->pluck('id');
            $progress = $trainee->moduleProgress
                ->whereIn('training_module_id', $availableModuleIds->all());
            $completedModules = $progress
                ->where('status', ModuleProgress::STATUS_COMPLETED)
                ->count();
            $inProgressModules = $progress
                ->where('status', ModuleProgress::STATUS_IN_PROGRESS)
                ->count();
            $totalModules = $availableModules->count();
            $progressPercent = $totalModules > 0
                ? (int) round($availableModules->sum(function (TrainingModule $module) use ($progress) {
                    return (int) ($progress->firstWhere('training_module_id', $module->id)?->progress_percent ?? 0);
                }) / $totalModules)
                : 0;
            $lastActivity = $progress
                ->filter(fn (ModuleProgress $record) => $record->last_viewed_at !== null)
                ->sortByDesc('last_viewed_at')
                ->first()?->last_viewed_at;

            return [$trainee->id => [
                'total_modules' => $totalModules,
                'completed_modules' => $completedModules,
                'in_progress_modules' => $inProgressModules,
                'progress_percent' => $progressPercent,
                'last_activity' => $lastActivity,
                'assessment_ready' => $totalModules > 0 && $completedModules === $totalModules,
            ]];
        });

        return view('admin.learning.trainees-lifecycle', [
            'batches' => $this->batches(),
            'filters' => $filters,
            'learningStatuses' => EnrollmentApplication::learningStatuses(),
            'statusCounts' => EnrollmentApplication::query()
                ->where('status', EnrollmentApplication::STATUS_APPROVED)
                ->selectRaw('learning_status, count(*) as aggregate')
                ->groupBy('learning_status')
                ->pluck('aggregate', 'learning_status'),
            'trainees' => $trainees,
            'traineeSummaries' => $traineeSummaries,
        ]);
    }

    public function exportTrainees(Request $request, TraineeRosterCsv $csv): StreamedResponse
    {
        $filters = $this->validateTraineeFilters($request);
        $trainees = $this->filteredTrainees($filters)
            ->with(['batch', 'moduleProgress'])
            ->get();
        $scope = filled($filters['batch_id'] ?? null) ? 'batch-'.$filters['batch_id'] : 'all-batches';

        AdminActivityLog::record($request->user(), 'admin.trainee-roster.exported', null, [
            'scope' => $scope,
            'row_count' => $trainees->count(),
        ]);

        return $csv->download($trainees, 'mcare-trainee-roster-'.$scope.'-'.now()->format('Y-m-d').'.csv');
    }

    public function updateTraineeStatus(
        Request $request,
        EnrollmentApplication $enrollmentApplication,
        CompletionEligibilityService $eligibility,
        RollingModuleReleaseService $releases,
    ): RedirectResponse
    {
        abort_unless(
            $enrollmentApplication->status === EnrollmentApplication::STATUS_APPROVED,
            422,
            'Only approved trainees can have a learning status.'
        );

        $validated = $request->validate([
            'learning_status' => ['required', Rule::in(array_keys(EnrollmentApplication::learningStatuses()))],
            'learning_status_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $previousStatus = $enrollmentApplication->learning_status ?: EnrollmentApplication::LEARNING_ACTIVE;

        if ($validated['learning_status'] === EnrollmentApplication::LEARNING_GRADUATED) {
            $completion = $eligibility->evaluate($enrollmentApplication->fresh('batch'));
            if (! $completion['eligible']) {
                $blockers = collect($completion['checks'])
                    ->reject(fn (array $check): bool => $check['passed'])
                    ->map(fn (array $check): string => $check['label'].': '.$check['detail'])
                    ->implode(' ');

                throw ValidationException::withMessages([
                    'learning_status' => 'Graduation is blocked. '.$blockers,
                ]);
            }
        }

        DB::transaction(function () use ($enrollmentApplication, $previousStatus, $request, $validated): void {
            $enrollmentApplication->update([
                'learning_status' => $validated['learning_status'],
                'learning_status_notes' => filled($validated['learning_status_notes'] ?? null)
                    ? trim($validated['learning_status_notes'])
                    : null,
                'learning_status_changed_at' => now(),
                'learning_status_changed_by_id' => $request->user()->id,
            ]);

            $user = User::query()->lockForUpdate()->find($enrollmentApplication->user_id);

            if ($user && $validated['learning_status'] === EnrollmentApplication::LEARNING_GRADUATED) {
                // Graduation unlocks Career Hub on the same trainee account.
                // Normalize old alumni-role records without replacing credentials or history.
                if ($user->role === 'alumni') {
                    $user->update(['role' => 'trainee']);
                }
                $user->alumniProfile()->firstOrCreate([], ['is_available_for_duty' => false]);
            } elseif ($user && $previousStatus === EnrollmentApplication::LEARNING_GRADUATED
                && $validated['learning_status'] !== EnrollmentApplication::LEARNING_GRADUATED) {
                $user->alumniProfile()->update([
                    'is_available_for_duty' => false,
                    'availability_updated_at' => now(),
                ]);
            }
        });

        if ($validated['learning_status'] === EnrollmentApplication::LEARNING_ACTIVE
            && $previousStatus !== EnrollmentApplication::LEARNING_ACTIVE) {
            $releases->assignCurrentTo($enrollmentApplication->fresh());
        }

        AdminActivityLog::record($request->user(), 'trainee.learning-status.updated', $enrollmentApplication, [
            'from' => $previousStatus,
            'to' => $validated['learning_status'],
            'notes' => $enrollmentApplication->learning_status_notes,
            'portal_role' => 'trainee',
            'career_hub_unlocked' => $validated['learning_status'] === EnrollmentApplication::LEARNING_GRADUATED,
        ]);

        return back()->with('saved', "{$enrollmentApplication->first_name} {$enrollmentApplication->last_name} is now {$enrollmentApplication->learningStatusLabel()}.");
    }

    public function modules(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'published' => ['nullable', Rule::in(['yes', 'no'])],
        ]);

        $query = TrainingModule::query()->with(['batch', 'trainer'])->latest('published_at');

        if ($batchId = $filters['batch_id'] ?? null) {
            $query->where('training_batch_id', $batchId);
        }

        if (isset($filters['published'])) {
            $query->where('is_published', $filters['published'] === 'yes');
        }

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(fn ($builder) => $builder
                ->where('title', 'like', "%{$search}%")
                ->orWhere('module_code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('trainer', fn ($trainer) => $trainer->where('name', 'like', "%{$search}%")));
        }

        return view('admin.learning.modules', [
            'batches' => $this->batches(),
            'trainers' => User::query()->where('role', 'trainer')->orderBy('name')->get(),
            'filters' => $filters,
            'modules' => $query->paginate(15)->withQueryString(),
            'catalogUnits' => \App\Support\CaregivingNcIiCatalog::units(),
            'coreUnits' => \App\Support\CaregivingNcIiCatalog::coreUnits(),
        ]);
    }

    public function storeModule(
        Request $request,
        RollingModuleReleaseService $releases,
    ): RedirectResponse
    {
        $validated = $request->validate([
            'trainer_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'trainer')),
            ],
            'training_batch_id' => ['required', 'integer', 'exists:training_batches,id'],
            'module_code' => ['nullable', 'string', 'max:50'],
            'competency_category' => ['nullable', 'string', Rule::in(['core', 'common', 'basic', 'custom'])],
            'title' => ['required', 'string', 'max:160'],
            'topic' => ['nullable', 'string', 'max:120'],
            'estimated_hours' => ['nullable', 'integer', 'min:1', 'max:500'],
            'description' => ['required', 'string', 'max:5000'],
            'module_file' => [
                'required',
                'file',
                'max:'.TrainingModuleFiles::MAX_UPLOAD_KB,
                new TrainingModuleFileType,
            ],
            'supplementary_files' => [
                'nullable',
                'array',
                'max:'.TrainingModuleFiles::MAX_SUPPLEMENTARY_FILES,
            ],
            'supplementary_files.*' => [
                'nullable',
                'file',
                'max:'.TrainingModuleFiles::MAX_SUPPLEMENTARY_UPLOAD_KB,
                new TrainingModuleFileType,
            ],
            'is_published' => ['nullable', 'boolean'],
        ], [
            'module_file.max' => 'Learning materials must not exceed 38MB on the current MCARE server.',
            'module_file.uploaded' => 'The upload did not reach MCARE. Check the server upload limit and try a smaller file.',
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('module_file');
        $path = null;
        $supplementaryList = [];

        try {
            $path = $file->store("training-modules/admin/{$request->user()->id}", 'local');
            if ($path === false) {
                throw new \RuntimeException('The primary learning material could not be stored.');
            }

            if ($request->hasFile('supplementary_files')) {
                $supplementaryList = TrainingModuleFiles::storeSupplementaryFiles(
                    $request->file('supplementary_files'),
                    $request->user()->id
                );
            }

            $module = DB::transaction(fn (): TrainingModule => TrainingModule::create([
                ...collect($validated)->except(['module_file', 'supplementary_files'])->all(),
                'file_path' => $path,
                'original_file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize() ?: 0,
                'supplementary_files' => $supplementaryList,
                'is_published' => $request->boolean('is_published'),
                'published_at' => $request->boolean('is_published') ? now() : null,
            ]));
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            TrainingModuleFiles::deleteSupplementaryFiles($supplementaryList);

            throw $exception;
        }

        AdminActivityLog::record($request->user(), 'admin.module.created', $module, [
            'trainer_id' => $module->trainer_id,
            'batch_id' => $module->training_batch_id,
            'module_code' => $module->module_code,
        ]);

        if ($module->is_published) {
            $releases->activate($module);
        }

        return back()->with('saved', "Module {$module->title} was added.");
    }

    public function destroyModule(Request $request, TrainingModule $module): RedirectResponse
    {
        if ($module->progressRecords()->exists()) {
            return back()->withErrors([
                'module' => 'This delivery has trainee assignments or evidence and cannot be deleted. Historical learning records must be preserved.',
            ]);
        }

        $title = $module->title;
        $path = $module->file_path;
        $supplementary = $module->supplementaryList();
        AdminActivityLog::record($request->user(), 'admin.module.removed', $module, [
            'title' => $title,
            'trainer_id' => $module->trainer_id,
        ]);

        $module->delete();
        Storage::disk('local')->delete($path);
        TrainingModuleFiles::deleteSupplementaryFiles($supplementary);

        return back()->with('saved', "Module {$title} was removed.");
    }

    public function certificates(Request $request): View
    {
        $filters = $request->validate([
            'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
            'eligibility' => ['nullable', Rule::in(['eligible', 'blocked'])],
        ]);

        $query = EnrollmentApplication::query()
            ->with(['batch', 'user'])
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->latest('reviewed_at');

        if ($batchId = $filters['batch_id'] ?? null) {
            $query->where('training_batch_id', $batchId);
        }

        if ($schedule = $filters['schedule'] ?? null) {
            $query->where('schedule_preference', $schedule);
        }

        if (($filters['eligibility'] ?? null) === 'eligible') {
            $query->where('payment_status', EnrollmentApplication::PAYMENT_PAID);
        } elseif (($filters['eligibility'] ?? null) === 'blocked') {
            $query->where('payment_status', '!=', EnrollmentApplication::PAYMENT_PAID);
        }

        return view('admin.learning.certificates', [
            'batches' => $this->batches(),
            'filters' => $filters,
            'records' => $query->paginate(15)->withQueryString(),
        ]);
    }

    public function alumniJobs(): View
    {
        return view('admin.learning.alumni-jobs', [
            'approvedTrainees' => EnrollmentApplication::query()->where('status', EnrollmentApplication::STATUS_APPROVED)->count(),
            'completedBatches' => TrainingBatch::query()->where('training_ends_at', '<=', now())->count(),
            'alumniAccounts' => User::query()
                ->whereHas('enrollmentApplication', fn ($query) => $query
                    ->where('status', EnrollmentApplication::STATUS_APPROVED)
                    ->where('learning_status', EnrollmentApplication::LEARNING_GRADUATED))
                ->count(),
        ]);
    }

    public function reports(): View
    {
        return view('admin.learning.reports', [
            'batches' => TrainingBatch::query()
                ->withCount([
                    'applications',
                    'applications as am_count' => fn ($query) => $query->where('schedule_preference', 'AM'),
                    'applications as pm_count' => fn ($query) => $query->where('schedule_preference', 'PM'),
                    'applications as approved_count' => fn ($query) => $query->where('status', EnrollmentApplication::STATUS_APPROVED),
                    'applications as paid_count' => fn ($query) => $query->where('payment_status', EnrollmentApplication::PAYMENT_PAID),
                    'modules',
                ])
                ->orderByDesc('year')
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function batches()
    {
        return TrainingBatch::query()
            ->withCount([
                'applications as approved_trainees_count' => fn ($query) => $query
                    ->where('status', EnrollmentApplication::STATUS_APPROVED),
            ])
            ->orderByDesc('year')
            ->orderBy('name')
            ->get();
    }

    private function validateTraineeFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
            'learning_status' => ['nullable', Rule::in(array_keys(EnrollmentApplication::learningStatuses()))],
            'training_state' => ['nullable', Rule::in(['not_started', 'in_progress', 'completed'])],
            'joined_from' => ['nullable', 'date'],
            'joined_to' => ['nullable', 'date', 'after_or_equal:joined_from'],
        ]);
    }

    private function filteredTrainees(array $filters)
    {
        $query = EnrollmentApplication::query()
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->latest('reviewed_at');

        if ($batchId = $filters['batch_id'] ?? null) {
            $query->where('training_batch_id', $batchId);
        }
        if ($schedule = $filters['schedule'] ?? null) {
            $query->where('schedule_preference', $schedule);
        }
        if ($learningStatus = $filters['learning_status'] ?? null) {
            $query->where('learning_status', $learningStatus);
        }
        if ($trainingState = $filters['training_state'] ?? null) {
            $query->whereHas('batch', function ($batchQuery) use ($trainingState) {
                match ($trainingState) {
                    'not_started' => $batchQuery->where(fn ($builder) => $builder->whereNull('training_starts_at')->orWhere('training_starts_at', '>', now())),
                    'in_progress' => $batchQuery->where('training_starts_at', '<=', now())->where(fn ($builder) => $builder->whereNull('training_ends_at')->orWhere('training_ends_at', '>', now())),
                    'completed' => $batchQuery->where('training_ends_at', '<=', now()),
                };
            });
        }
        if ($joinedFrom = $filters['joined_from'] ?? null) {
            $query->whereDate('reviewed_at', '>=', $joinedFrom);
        }
        if ($joinedTo = $filters['joined_to'] ?? null) {
            $query->whereDate('reviewed_at', '<=', $joinedTo);
        }
        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(fn ($builder) => $builder
                ->where('email', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%"));
        }

        return $query;
    }
}
