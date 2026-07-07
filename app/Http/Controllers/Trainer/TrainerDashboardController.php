<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\TrainerAnnouncement;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrainerDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $trainer = $request->user();
        $activeBatch = TrainingBatch::active();
        $traineeQuery = $this->approvedTraineesFor($activeBatch);

        return view('trainer.dashboard', [
            'activeBatch' => $activeBatch,
            'modules' => TrainingModule::query()
                ->with('batch')
                ->where('trainer_id', $trainer->id)
                ->latest('published_at')
                ->paginate(8, ['*'], 'modules_page'),
            'announcements' => TrainerAnnouncement::query()
                ->with('batch')
                ->where('trainer_id', $trainer->id)
                ->when($activeBatch, fn ($query) => $query->where(function ($inner) use ($activeBatch) {
                    $inner->whereNull('training_batch_id')
                        ->orWhere('training_batch_id', $activeBatch->id);
                }))
                ->latest('posted_at')
                ->take(5)
                ->get(),
            'amTrainees' => (clone $traineeQuery)->where('schedule_preference', 'AM')->get(),
            'pmTrainees' => (clone $traineeQuery)->where('schedule_preference', 'PM')->get(),
            'stats' => [
                'modules' => TrainingModule::query()->where('trainer_id', $trainer->id)->count(),
                'trainees' => (clone $traineeQuery)->count(),
                'am' => (clone $traineeQuery)->where('schedule_preference', 'AM')->count(),
                'pm' => (clone $traineeQuery)->where('schedule_preference', 'PM')->count(),
            ],
        ]);
    }

    public function storeModule(Request $request): RedirectResponse
    {
        $safeText = ['not_regex:/[<>"\'`;{}|\\\\]/u'];
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160', ...$safeText],
            'description' => ['required', 'string', 'max:1200', ...$safeText],
            'module_file' => ['required', 'file', 'mimes:pdf,doc,docx,ppt,pptx', 'max:20480'],
        ], [
            'not_regex' => 'This field contains characters that are not allowed for security reasons.',
            'module_file.mimes' => 'Training modules must be PDF, DOC, DOCX, PPT, or PPTX files.',
            'module_file.max' => 'Training modules must not exceed 20MB.',
        ]);

        $file = $request->file('module_file');
        $trainer = $request->user();
        $activeBatch = TrainingBatch::active();

        // Keep trainer materials on the private disk so LMS access rules can enforce logging later.
        $path = $file->store("training-modules/{$trainer->id}", 'local');

        $module = TrainingModule::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $activeBatch?->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'file_path' => $path,
            'original_file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize() ?: 0,
            'is_published' => true,
            'published_at' => now(),
        ]);

        AdminActivityLog::record($trainer, 'trainer.module.uploaded', $module, [
            'title' => $module->title,
            'batch' => $activeBatch ? $activeBatch->name.' '.$activeBatch->year : null,
        ]);

        return redirect()
            ->to(route('trainer.dashboard').'#modules')
            ->with('saved', 'Training module published for trainees.');
    }

    public function downloadModule(Request $request, TrainingModule $module): StreamedResponse
    {
        abort_unless($module->trainer_id === $request->user()->id, 403);

        AdminActivityLog::record($request->user(), 'trainer.module.downloaded', $module, [
            'title' => $module->title,
        ]);

        return Storage::disk('local')->download($module->file_path, $module->original_file_name);
    }

    private function approvedTraineesFor(?TrainingBatch $activeBatch)
    {
        // The trainer dashboard treats approved applications as the current official trainee list.
        return EnrollmentApplication::query()
            ->with(['batch', 'user'])
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->when($activeBatch, fn ($query) => $query->where('training_batch_id', $activeBatch->id))
            ->orderBy('last_name')
            ->orderBy('first_name');
    }
}
