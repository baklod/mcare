<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\User;
use App\Services\TraineeRosterCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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

        $query = $this->filteredTrainees($filters)->with(['batch', 'user']);

        return view('admin.learning.trainees-lifecycle', [
            'batches' => $this->batches(),
            'filters' => $filters,
            'learningStatuses' => EnrollmentApplication::learningStatuses(),
            'statusCounts' => EnrollmentApplication::query()
                ->where('status', EnrollmentApplication::STATUS_APPROVED)
                ->selectRaw('learning_status, count(*) as aggregate')
                ->groupBy('learning_status')
                ->pluck('aggregate', 'learning_status'),
            'trainees' => $query->paginate(20)->withQueryString(),
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

    public function updateTraineeStatus(Request $request, EnrollmentApplication $enrollmentApplication): RedirectResponse
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

        $enrollmentApplication->update([
            'learning_status' => $validated['learning_status'],
            'learning_status_notes' => filled($validated['learning_status_notes'] ?? null)
                ? trim($validated['learning_status_notes'])
                : null,
            'learning_status_changed_at' => now(),
            'learning_status_changed_by_id' => $request->user()->id,
        ]);

        AdminActivityLog::record($request->user(), 'trainee.learning-status.updated', $enrollmentApplication, [
            'from' => $previousStatus,
            'to' => $validated['learning_status'],
            'notes' => $enrollmentApplication->learning_status_notes,
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
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('trainer', fn ($trainer) => $trainer->where('name', 'like', "%{$search}%")));
        }

        return view('admin.learning.modules', [
            'batches' => $this->batches(),
            'trainers' => User::query()->where('role', 'trainer')->orderBy('name')->get(),
            'filters' => $filters,
            'modules' => $query->paginate(15)->withQueryString(),
        ]);
    }

    public function storeModule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'trainer_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'trainer')),
            ],
            'training_batch_id' => ['required', 'integer', 'exists:training_batches,id'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:1200'],
            'module_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,mp4,webm', 'max:102400'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('module_file');
        $path = $file->store("training-modules/admin/{$request->user()->id}", 'local');

        $module = TrainingModule::create([
            ...collect($validated)->except(['module_file'])->all(),
            'file_path' => $path,
            'original_file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize() ?: 0,
            'is_published' => $request->boolean('is_published'),
            'published_at' => $request->boolean('is_published') ? now() : null,
        ]);

        AdminActivityLog::record($request->user(), 'admin.module.created', $module, [
            'trainer_id' => $module->trainer_id,
            'batch_id' => $module->training_batch_id,
        ]);

        return back()->with('saved', "Module {$module->title} was added.");
    }

    public function destroyModule(Request $request, TrainingModule $module): RedirectResponse
    {
        $title = $module->title;
        AdminActivityLog::record($request->user(), 'admin.module.removed', $module, [
            'title' => $title,
            'trainer_id' => $module->trainer_id,
        ]);

        Storage::disk('local')->delete($module->file_path);
        $module->delete();

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
            'alumniAccounts' => User::query()->where('role', 'alumni')->count(),
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
