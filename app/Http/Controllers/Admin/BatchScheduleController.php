<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\TrainingCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BatchScheduleController extends Controller
{
    public function index(Request $request, TrainingCalendarService $calendarService): View
    {
        return $this->scheduleView($request, $calendarService);
    }

    public function edit(
        Request $request,
        TrainingBatch $trainingBatch,
        TrainingCalendarService $calendarService,
    ): View {
        return $this->scheduleView($request, $calendarService, $trainingBatch);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $batch = TrainingBatch::create($validated);

        AdminActivityLog::record($request->user(), 'batch.created', $batch, [
            'name' => $batch->name,
            'training_program_id' => $batch->training_program_id,
            'year' => $batch->year,
            'active' => $batch->is_active,
            'public' => $batch->show_on_enrollment_page,
            'trainer_id' => $batch->trainer_id,
            'enrollment_ends_at' => $batch->enrollment_ends_at?->toDateTimeString(),
        ]);

        return redirect()
            ->route('admin.schedules.index')
            ->with('saved', 'Batch schedule created.');
    }

    public function update(Request $request, TrainingBatch $trainingBatch): RedirectResponse
    {
        $validated = $this->validated($request, $trainingBatch);

        $before = $trainingBatch->only([
            'training_program_id',
            'name',
            'year',
            'trainer_id',
            'is_active',
            'show_on_enrollment_page',
            'enrollment_starts_at',
            'enrollment_ends_at',
            'training_starts_at',
            'training_ends_at',
            'am_start_time',
            'am_end_time',
            'am_room',
            'am_days',
            'pm_start_time',
            'pm_end_time',
            'pm_room',
            'pm_days',
        ]);

        $trainingBatch->update($validated);

        AdminActivityLog::record($request->user(), 'batch.updated', $trainingBatch, [
            'before' => $before,
            'after' => $trainingBatch->fresh()->only(array_keys($before)),
        ]);

        return redirect()
            ->route('admin.schedules.index')
            ->with('saved', 'Batch schedule updated.');
    }

    public function destroy(Request $request, TrainingBatch $trainingBatch): RedirectResponse
    {
        $relatedRecords = collect([
            'applicants' => $trainingBatch->applications()->count(),
            'learning modules' => $trainingBatch->modules()->count(),
            'announcements' => $trainingBatch->announcements()->count(),
            'quizzes' => $trainingBatch->quizzes()->count(),
            'official documents' => $trainingBatch->officialDocuments()->count(),
            'batch exports' => $trainingBatch->documentExports()->count(),
        ])->filter();

        if ($relatedRecords->isNotEmpty()) {
            return back()->withErrors([
                'batch' => 'This batch cannot be deleted because it has related records: '
                    .$relatedRecords->map(fn (int $count, string $label) => "{$count} {$label}")->implode(', ').'.',
            ]);
        }

        $batchLabel = "{$trainingBatch->name} {$trainingBatch->year}";

        DB::transaction(function () use ($request, $trainingBatch): void {
            $lockedBatch = TrainingBatch::query()->lockForUpdate()->findOrFail($trainingBatch->id);

            // Re-check inside the transaction so a concurrent enrollment cannot leave an orphaned batch relation.
            if ($lockedBatch->applications()->exists()
                || $lockedBatch->modules()->exists()
                || $lockedBatch->announcements()->exists()
                || $lockedBatch->quizzes()->exists()
                || $lockedBatch->officialDocuments()->exists()
                || $lockedBatch->documentExports()->exists()) {
                abort(409, 'This batch received a related record while deletion was in progress.');
            }

            AdminActivityLog::record($request->user(), 'batch.deleted', $lockedBatch, [
                'name' => $lockedBatch->name,
                'year' => $lockedBatch->year,
                'trainer_id' => $lockedBatch->trainer_id,
            ]);

            $lockedBatch->delete();
        });

        return redirect()
            ->route('admin.schedules.index')
            ->with('saved', "Batch schedule {$batchLabel} deleted.");
    }

    private function validated(Request $request, ?TrainingBatch $batch = null): array
    {
        $safeText = ['not_regex:/[<>"\'`;{}|\\\\]/u'];

        // Existing integrations created batches before programs were explicit.
        // Use the batch's program (or the oldest active default) only when the
        // request omits it; the current admin UI always submits an explicit choice.
        if (! $request->filled('training_program_id')) {
            $request->merge([
                'training_program_id' => $batch?->training_program_id
                    ?: TrainingProgram::query()->active()->oldest('id')->value('id'),
            ]);
        }

        // Normalize time inputs by trimming seconds if present (e.g. "08:00:00" -> "08:00")
        foreach (['am_start_time', 'am_end_time', 'pm_start_time', 'pm_end_time'] as $timeField) {
            if ($request->filled($timeField)) {
                $rawTime = trim((string) $request->input($timeField));
                $normalized = preg_match('/^(\d{1,2}:\d{2})(:\d{2})?$/', $rawTime, $matches)
                    ? str_pad($matches[1], 5, '0', STR_PAD_LEFT)
                    : $rawTime;
                $request->merge([$timeField => $normalized]);
            }
        }

        $validated = $request->validate([
            'training_program_id' => ['required', 'integer', 'exists:training_programs,id'],
            'name' => [
                'required',
                'string',
                'max:120',
                ...$safeText,
                Rule::unique('training_batches', 'name')
                    ->where('training_program_id', $request->integer('training_program_id'))
                    ->where('year', $request->integer('year'))
                    ->ignore($batch?->id),
            ],
            'year' => ['required', 'integer', 'min:2024', 'max:2100'],
            'trainer_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'trainer')),
            ],
            'is_active' => ['nullable', 'boolean'],
            'show_on_enrollment_page' => ['nullable', 'boolean'],
            'is_continuous_enrollment' => ['nullable', 'boolean'],
            'enrollment_starts_at' => ['nullable', 'date'],
            // A new batch must open a future enrollment window. Existing
            // batches may have closed enrollment deadlines and still need
            // schedule, room, or trainer edits afterward.
            'enrollment_ends_at' => [
                'nullable',
                'required_unless:is_continuous_enrollment,1',
                'date',
                ...($batch ? [] : ['after:now']),
            ],
            'training_starts_at' => ['nullable', 'required_with:training_ends_at', 'date'],
            'training_ends_at' => ['nullable', 'required_with:training_starts_at', 'date', 'after_or_equal:training_starts_at'],
            'am_start_time' => ['nullable', 'date_format:H:i'],
            'am_end_time' => ['nullable', 'date_format:H:i', 'after:am_start_time'],
            'am_room' => ['nullable', 'string', 'max:120', ...$safeText],
            'am_days' => ['required', 'string', 'max:50', ...$safeText],
            'pm_start_time' => ['nullable', 'date_format:H:i'],
            'pm_end_time' => ['nullable', 'date_format:H:i', 'after:pm_start_time'],
            'pm_room' => ['nullable', 'string', 'max:120', ...$safeText],
            'pm_days' => ['required', 'string', 'max:50', ...$safeText],
            'notes' => ['nullable', 'string', 'max:1000', ...$safeText],
        ], [
            'name.unique' => 'A batch with this name and year already exists for the selected program.',
            'not_regex' => 'This field contains characters that are not allowed for security reasons.',
            'enrollment_ends_at.after' => 'Enrollment deadline must be a future date and time.',
            'training_ends_at.after_or_equal' => 'Training end must be on or later than the training start date.',
            'am_end_time.after' => 'AM end time must be later than AM start time.',
            'pm_end_time.after' => 'PM end time must be later than PM start time.',
            'trainer_id.exists' => 'The selected trainer is invalid or does not have trainer permissions.',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['show_on_enrollment_page'] = $request->boolean('show_on_enrollment_page');
        $validated['is_continuous_enrollment'] = $request->boolean('is_continuous_enrollment');
        if ($validated['is_continuous_enrollment']) {
            $validated['enrollment_ends_at'] = null;
        }
        $validated['am_days'] = strtoupper(trim($validated['am_days']));
        $validated['pm_days'] = strtoupper(trim($validated['pm_days']));

        return $validated;
    }

    private function scheduleView(
        Request $request,
        TrainingCalendarService $calendarService,
        ?TrainingBatch $editingBatch = null,
    ): View {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $activeBatch = TrainingBatch::active();
        $defaultMonth = $calendarService->suggestedMonth($editingBatch ?? $activeBatch);
        $month = isset($validated['month'])
            ? Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth()
            : $defaultMonth;
        $calendarBatches = TrainingBatch::query()
            ->with('program')
            ->orderByDesc('is_active')
            ->orderByDesc('year')
            ->orderBy('name')
            ->get();
        $calendarSessions = $calendarService->monthForBatches($calendarBatches, $month);

        return view('admin.schedules.index', [
            'batches' => TrainingBatch::query()
                ->with(['trainer', 'program'])
                ->withCount([
                    'applications',
                    'modules',
                    'announcements',
                    'quizzes',
                    'officialDocuments',
                    'documentExports',
                ])
                ->orderByDesc('is_active')
                ->orderByDesc('year')
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString(),
            'programs' => TrainingProgram::query()
                ->withCount('batches')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
            'trainers' => User::query()
                ->where('role', 'trainer')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'editingBatch' => $editingBatch,
            'calendarMonth' => $month,
            'calendarSessions' => $calendarSessions,
            'calendarSelectedDate' => $validated['date'] ?? null,
        ]);
    }
}
