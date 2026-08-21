<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Rules\TrainingModuleFileType;
use App\Support\TrainingModuleFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrainingModuleController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedPayload($request, true);
        [$batchId, $targetTrainee] = $this->resolveAudience($validated);
        $this->assertTrainerBatch($request, $batchId, $targetTrainee);
        $trainer = $request->user();
        /** @var UploadedFile $file */
        $file = $request->file('module_file');
        $path = $file->store("training-modules/{$trainer->id}", 'local');

        try {
            $module = DB::transaction(function () use (
                $validated,
                $trainer,
                $file,
                $path,
                $batchId,
                $targetTrainee,
                $request,
            ): TrainingModule {
                $published = $request->has('is_published')
                    ? $request->boolean('is_published')
                    : true;

                return TrainingModule::create([
                    'trainer_id' => $trainer->id,
                    'training_batch_id' => $batchId,
                    'target_enrollment_application_id' => $targetTrainee?->id,
                    'module_code' => $validated['module_code'] ?? null,
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'topic' => $validated['topic'] ?? null,
                    'available_at' => $validated['available_at'] ?? null,
                    'due_at' => $validated['due_at'] ?? null,
                    'position' => $validated['position'] ?? 0,
                    'file_path' => $path,
                    'original_file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize() ?: 0,
                    'is_published' => $published,
                    'published_at' => $published ? now() : null,
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        AdminActivityLog::record($trainer, 'trainer.module.uploaded', $module, [
            'title' => $module->title,
            'batch_id' => $batchId,
            'audience' => $targetTrainee ? 'trainee' : 'batch',
            'target_trainee_id' => $targetTrainee?->id,
            'published' => $module->is_published,
        ]);

        return redirect()
            ->route('trainer.resources')
            ->with('saved', $module->is_published
                ? 'Learning material published.'
                : 'Learning material saved as a draft.');
    }

    public function update(
        Request $request,
        TrainingModule $module,
    ): RedirectResponse {
        $this->authorize('update', $module);

        $validated = $this->validatedPayload($request, false);
        [$batchId, $targetTrainee] = $this->resolveAudience($validated);
        $this->assertTrainerBatch($request, $batchId, $targetTrainee);
        $replacement = $request->file('module_file');
        $replacementPath = $replacement?->store("training-modules/{$request->user()->id}", 'local');
        $oldPath = $module->file_path;

        try {
            DB::transaction(function () use (
                $validated,
                $request,
                $module,
                $batchId,
                $targetTrainee,
                $replacement,
                $replacementPath,
            ): void {
                $published = $request->has('is_published')
                    ? $request->boolean('is_published')
                    : $module->is_published;
                $attributes = [
                    'training_batch_id' => $batchId,
                    'target_enrollment_application_id' => $targetTrainee?->id,
                    'module_code' => $validated['module_code'] ?? null,
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'topic' => $validated['topic'] ?? null,
                    'available_at' => $validated['available_at'] ?? null,
                    'due_at' => $validated['due_at'] ?? null,
                    'position' => $validated['position'] ?? 0,
                    'is_published' => $published,
                    'published_at' => $published ? ($module->published_at ?? now()) : null,
                ];

                if ($replacement && $replacementPath) {
                    $attributes = [
                        ...$attributes,
                        'file_path' => $replacementPath,
                        'original_file_name' => $replacement->getClientOriginalName(),
                        'mime_type' => $replacement->getMimeType(),
                        'file_size' => $replacement->getSize() ?: 0,
                    ];
                }

                $module->update($attributes);
            });
        } catch (\Throwable $exception) {
            if ($replacementPath) {
                Storage::disk('local')->delete($replacementPath);
            }

            throw $exception;
        }

        if ($replacementPath && $oldPath !== $replacementPath) {
            Storage::disk('local')->delete($oldPath);
        }

        AdminActivityLog::record($request->user(), 'trainer.module.updated', $module, [
            'title' => $module->title,
            'batch_id' => $module->training_batch_id,
            'published' => $module->is_published,
            'file_replaced' => (bool) $replacementPath,
        ]);

        return redirect()
            ->route('trainer.resources')
            ->with('saved', 'Learning material updated.');
    }

    public function destroy(
        Request $request,
        TrainingModule $module,
    ): RedirectResponse {
        $this->authorize('delete', $module);

        $title = $module->title;
        $path = $module->file_path;

        AdminActivityLog::record($request->user(), 'trainer.module.deleted', $module, [
            'title' => $title,
            'batch_id' => $module->training_batch_id,
        ]);
        $module->delete();
        Storage::disk('local')->delete($path);

        return redirect()
            ->route('trainer.resources')
            ->with('saved', "Learning material {$title} was removed.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, bool $fileRequired): array
    {
        $activeBatch = TrainingBatch::assignedTo($request->user());
        $request->merge([
            'audience_type' => $request->input('audience_type', 'batch'),
            'training_batch_id' => $request->input('training_batch_id', $activeBatch?->id),
        ]);

        $validated = $request->validate([
            'module_code' => ['nullable', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:5000'],
            'topic' => ['nullable', 'string', 'max:120'],
            'module_file' => [
                $fileRequired ? 'required' : 'nullable',
                'file',
                'max:'.TrainingModuleFiles::MAX_UPLOAD_KB,
                new TrainingModuleFileType,
            ],
            'audience_type' => ['required', Rule::in(['batch', 'trainee'])],
            'training_batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'target_enrollment_application_id' => [
                'nullable',
                'integer',
                Rule::exists('enrollment_applications', 'id')->where(
                    fn ($query) => $query->where('status', EnrollmentApplication::STATUS_APPROVED)
                ),
            ],
            'available_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'position' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'is_published' => ['nullable', 'boolean'],
        ], [
            'module_file.max' => 'Learning materials must not exceed 38MB on the current MCARE server.',
            'module_file.uploaded' => 'The upload did not reach MCARE. Check the server upload limit and try a smaller file.',
        ]);

        if (
            filled($validated['available_at'] ?? null)
            && filled($validated['due_at'] ?? null)
            && Carbon::parse($validated['due_at'])->lte(Carbon::parse($validated['available_at']))
        ) {
            throw ValidationException::withMessages([
                'due_at' => 'The due date must be after the material becomes available.',
            ]);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: int, 1: EnrollmentApplication|null}
     */
    private function resolveAudience(array $validated): array
    {
        $targetTrainee = $validated['audience_type'] === 'trainee'
            ? EnrollmentApplication::query()->find($validated['target_enrollment_application_id'] ?? null)
            : null;
        $batchId = $targetTrainee?->training_batch_id ?? ($validated['training_batch_id'] ?? null);

        if (! $batchId || ($validated['audience_type'] === 'trainee' && ! $targetTrainee)) {
            throw ValidationException::withMessages([
                'audience_type' => 'Choose a batch or an approved trainee before saving.',
            ]);
        }

        return [(int) $batchId, $targetTrainee];
    }

    private function assertTrainerBatch(
        Request $request,
        int $batchId,
        ?EnrollmentApplication $targetTrainee,
    ): void {
        $assignedBatch = TrainingBatch::assignedTo($request->user());

        if (! $assignedBatch || $batchId !== (int) $assignedBatch->id) {
            throw ValidationException::withMessages([
                'training_batch_id' => 'Learning materials can only be published to the trainer\'s assigned batch.',
            ]);
        }

        if ($targetTrainee && (int) $targetTrainee->training_batch_id !== $batchId) {
            throw ValidationException::withMessages([
                'target_enrollment_application_id' => 'The selected trainee is outside the trainer\'s assigned batch.',
            ]);
        }
    }
}
