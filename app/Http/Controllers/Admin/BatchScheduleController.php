<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\TrainingBatch;
use App\Services\TrainingCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
    ): View
    {
        return $this->scheduleView($request, $calendarService, $trainingBatch);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $batch = TrainingBatch::create($validated);
        $this->syncActiveBatch($batch);

        AdminActivityLog::record($request->user(), 'batch.created', $batch, [
            'name' => $batch->name,
            'year' => $batch->year,
            'active' => $batch->is_active,
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
            'name',
            'year',
            'is_active',
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
        $this->syncActiveBatch($trainingBatch);

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
        if ($trainingBatch->applications()->exists()) {
            return back()->withErrors([
                'batch' => 'This batch already has applicants and cannot be deleted.',
            ]);
        }

        AdminActivityLog::record($request->user(), 'batch.deleted', $trainingBatch, [
            'name' => $trainingBatch->name,
            'year' => $trainingBatch->year,
        ]);

        $trainingBatch->delete();

        return redirect()
            ->route('admin.schedules.index')
            ->with('saved', 'Batch schedule deleted.');
    }

    private function validated(Request $request, ?TrainingBatch $batch = null): array
    {
        $safeText = ['not_regex:/[<>"\'`;{}|\\\\]/u'];

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                ...$safeText,
                Rule::unique('training_batches', 'name')
                    ->where('year', $request->integer('year'))
                    ->ignore($batch?->id),
            ],
            'year' => ['required', 'integer', 'min:2024', 'max:2100'],
            'is_active' => ['nullable', 'boolean'],
            'enrollment_starts_at' => ['nullable', 'date'],
            'enrollment_ends_at' => ['required', 'date', 'after:now'],
            'training_starts_at' => ['nullable', 'required_with:training_ends_at', 'date'],
            'training_ends_at' => ['nullable', 'required_with:training_starts_at', 'date', 'after:training_starts_at'],
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
            'name.unique' => 'A batch with this name and year already exists.',
            'not_regex' => 'This field contains characters that are not allowed for security reasons.',
            'enrollment_ends_at.after' => 'Enrollment deadline must be a future date and time.',
            'training_ends_at.after' => 'Training end must be later than the training start.',
            'am_end_time.after' => 'AM end time must be later than AM start time.',
            'pm_end_time.after' => 'PM end time must be later than PM start time.',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['am_days'] = strtoupper(trim($validated['am_days']));
        $validated['pm_days'] = strtoupper(trim($validated['pm_days']));

        return $validated;
    }

    private function syncActiveBatch(TrainingBatch $batch): void
    {
        $batch->forceFill([
            'is_active' => (bool) $batch->is_active,
        ])->save();

        if (! $batch->is_active) {
            return;
        }

        TrainingBatch::query()
            ->whereKeyNot($batch->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);
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
            ->orderByDesc('is_active')
            ->orderByDesc('year')
            ->orderBy('name')
            ->get();
        $calendarSessions = $calendarService->monthForBatches($calendarBatches, $month);

        return view('admin.schedules.index', [
            'batches' => TrainingBatch::query()
                ->withCount('applications')
                ->orderByDesc('is_active')
                ->orderByDesc('year')
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString(),
            'editingBatch' => $editingBatch,
            'calendarMonth' => $month,
            'calendarSessions' => $calendarSessions,
            'calendarSelectedDate' => $validated['date'] ?? null,
        ]);
    }
}
