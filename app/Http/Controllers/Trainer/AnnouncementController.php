<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\TrainerAnnouncement;
use App\Models\TrainingBatch;
use App\Models\User;
use App\Notifications\LmsAnnouncementPublished;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $trainer = $request->user();
        $filters = $request->validate([
            'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
        ]);
        $announcements = TrainerAnnouncement::query()
            ->with(['batch', 'trainer'])
            ->where('trainer_id', $trainer->id)
            ->when($filters['batch_id'] ?? null, fn ($query, $batchId) => $query->where('training_batch_id', $batchId))
            ->orderByDesc('is_pinned')
            ->orderByDesc('posted_at')
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('trainer.stream', [
            'announcements' => $announcements,
            'batches' => TrainingBatch::query()
                ->orderByDesc('is_active')
                ->orderByDesc('year')
                ->orderBy('name')
                ->get(),
            'streamStats' => [
                'total' => TrainerAnnouncement::query()->where('trainer_id', $trainer->id)->count(),
                'published' => TrainerAnnouncement::query()
                    ->where('trainer_id', $trainer->id)
                    ->where('is_published', true)
                    ->where(fn ($query) => $query->whereNull('posted_at')->orWhere('posted_at', '<=', now()))
                    ->count(),
                'scheduled' => TrainerAnnouncement::query()
                    ->where('trainer_id', $trainer->id)
                    ->where('is_published', true)
                    ->where('posted_at', '>', now())
                    ->count(),
                'learners' => EnrollmentApplication::query()
                    ->where('status', EnrollmentApplication::STATUS_APPROVED)
                    ->when($filters['batch_id'] ?? null, fn ($query, $batchId) => $query->where('training_batch_id', $batchId))
                    ->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedPayload($request);
        $published = $request->has('is_published') ? $request->boolean('is_published') : true;

        $announcement = TrainerAnnouncement::create([
            ...$validated,
            'trainer_id' => $request->user()->id,
            'is_pinned' => $request->boolean('is_pinned'),
            'is_published' => $published,
            'posted_at' => $published ? ($validated['posted_at'] ?? now()) : ($validated['posted_at'] ?? null),
        ]);

        AdminActivityLog::record($request->user(), 'trainer.announcement.created', $announcement, [
            'title' => $announcement->title,
            'batch_id' => $announcement->training_batch_id,
            'kind' => $announcement->kind,
            'published' => $announcement->is_published,
        ]);

        if ($this->shouldNotifyTrainees($announcement)) {
            $this->notifyTrainees($announcement);
        }

        return redirect()
            ->route('trainer.stream')
            ->with('saved', $announcement->is_published
                ? 'Announcement posted to the class stream.'
                : 'Announcement saved as a draft.');
    }

    public function update(
        Request $request,
        TrainerAnnouncement $announcement,
    ): RedirectResponse {
        $this->authorize('update', $announcement);

        $wasPublished = $announcement->is_published;
        $validated = $this->validatedPayload($request);
        $published = $request->has('is_published')
            ? $request->boolean('is_published')
            : $announcement->is_published;

        $announcement->update([
            ...$validated,
            'is_pinned' => $request->boolean('is_pinned'),
            'is_published' => $published,
            'posted_at' => $published
                ? ($validated['posted_at'] ?? $announcement->posted_at ?? now())
                : ($validated['posted_at'] ?? null),
        ]);

        AdminActivityLog::record($request->user(), 'trainer.announcement.updated', $announcement, [
            'title' => $announcement->title,
            'batch_id' => $announcement->training_batch_id,
            'published' => $announcement->is_published,
        ]);

        if (! $wasPublished && $this->shouldNotifyTrainees($announcement)) {
            $this->notifyTrainees($announcement);
        }

        return redirect()
            ->route('trainer.stream')
            ->with('saved', 'Announcement updated.');
    }

    public function destroy(
        Request $request,
        TrainerAnnouncement $announcement,
    ): RedirectResponse {
        $this->authorize('delete', $announcement);

        $title = $announcement->title;
        AdminActivityLog::record($request->user(), 'trainer.announcement.deleted', $announcement, [
            'title' => $title,
            'batch_id' => $announcement->training_batch_id,
        ]);
        $announcement->delete();

        return redirect()
            ->route('trainer.stream')
            ->with('saved', "Announcement {$title} was removed.");
    }

    /**
     * Validate the shared create/edit fields while leaving ownership on the server.
     *
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request): array
    {
        $request->merge([
            'audience' => $request->filled('audience')
                ? $request->input('audience')
                : 'trainees',
        ]);

        $validated = $request->validate([
            'training_batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'kind' => ['required', Rule::in(['announcement', 'news', 'reminder'])],
            'audience' => ['required', Rule::in(['all', 'trainees'])],
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:5000'],
            'posted_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        if (
            filled($validated['posted_at'] ?? null)
            && filled($validated['expires_at'] ?? null)
            && Carbon::parse($validated['expires_at'])->lte(Carbon::parse($validated['posted_at']))
        ) {
            throw ValidationException::withMessages([
                'expires_at' => 'The hide-after time must be later than the posting time.',
            ]);
        }

        return $validated;
    }

    private function shouldNotifyTrainees(TrainerAnnouncement $announcement): bool
    {
        // Scheduled announcements should notify when they become visible, not before.
        return $announcement->isVisibleNow()
            && in_array($announcement->audience, ['all', 'trainees'], true);
    }

    private function notifyTrainees(TrainerAnnouncement $announcement): void
    {
        $traineeIds = EnrollmentApplication::query()
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->when(
                $announcement->training_batch_id,
                fn ($query) => $query->where('training_batch_id', $announcement->training_batch_id)
            )
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->unique();

        $trainees = User::query()
            ->where('role', 'trainee')
            ->whereIn('id', $traineeIds)
            ->get();

        if ($trainees->isNotEmpty()) {
            Notification::send($trainees, new LmsAnnouncementPublished($announcement));
        }
    }
}
