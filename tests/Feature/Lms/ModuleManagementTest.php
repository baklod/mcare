<?php

namespace Tests\Feature\Lms;

use App\Models\ModuleProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class ModuleManagementTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_owner_can_edit_metadata_and_replace_then_delete_a_private_module_file(): void
    {
        Storage::fake('local');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $module = $this->lmsModule($trainer, $batch);
        Storage::disk('local')->put($module->file_path, '%PDF old lesson');
        $originalPath = $module->file_path;

        $basePayload = [
            'audience_type' => 'batch',
            'training_batch_id' => $batch->id,
            'title' => 'Updated patient transfer',
            'description' => 'Updated lesson description.',
            'topic' => 'Mobility support',
            'available_at' => now()->subHour()->toDateTimeString(),
            'due_at' => now()->addWeek()->toDateTimeString(),
            'position' => 2,
            'is_published' => '1',
        ];

        $this->actingAs($trainer)
            ->patch(route('trainer.modules.update', $module), $basePayload)
            ->assertRedirect(route('trainer.resources'))
            ->assertSessionHas('saved');

        $module->refresh();
        $this->assertSame($originalPath, $module->file_path);
        Storage::disk('local')->assertExists($originalPath);

        $this->actingAs($trainer)
            ->patch(route('trainer.modules.update', $module), [
                ...$basePayload,
                'module_file' => UploadedFile::fake()
                    ->create('updated-transfer.pdf', 80, 'application/pdf'),
            ])
            ->assertRedirect(route('trainer.resources'));

        $module->refresh();
        $this->assertNotSame($originalPath, $module->file_path);
        $this->assertSame('updated-transfer.pdf', $module->original_file_name);
        Storage::disk('local')->assertMissing($originalPath);
        Storage::disk('local')->assertExists($module->file_path);
        $replacementPath = $module->file_path;

        $this->actingAs($trainer)
            ->delete(route('trainer.modules.destroy', $module))
            ->assertRedirect(route('trainer.resources'));

        $this->assertDatabaseMissing('training_modules', ['id' => $module->id]);
        Storage::disk('local')->assertMissing($replacementPath);
    }

    public function test_trainer_cannot_update_or_delete_another_trainers_module(): void
    {
        Storage::fake('local');
        $owner = $this->lmsUser('trainer');
        $otherTrainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $module = $this->lmsModule($owner, $batch);
        Storage::disk('local')->put($module->file_path, '%PDF owner lesson');

        $this->actingAs($otherTrainer)
            ->patch(route('trainer.modules.update', $module), [
                'audience_type' => 'batch',
                'training_batch_id' => $batch->id,
                'title' => 'Unauthorized title',
                'description' => 'This must not be saved.',
                'is_published' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($otherTrainer)
            ->delete(route('trainer.modules.destroy', $module))
            ->assertForbidden();

        $this->assertDatabaseHas('training_modules', [
            'id' => $module->id,
            'title' => 'Safe Patient Transfer',
        ]);
        Storage::disk('local')->assertExists($module->file_path);
    }

    public function test_invalid_replacement_does_not_change_or_remove_the_existing_module_file(): void
    {
        Storage::fake('local');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $module = $this->lmsModule($trainer, $batch);
        Storage::disk('local')->put($module->file_path, '%PDF original lesson');
        $originalPath = $module->file_path;

        $this->actingAs($trainer)
            ->from(route('trainer.resources'))
            ->patch(route('trainer.modules.update', $module), [
                'audience_type' => 'batch',
                'training_batch_id' => $batch->id,
                'title' => 'Rejected replacement',
                'description' => 'The executable upload must be rejected.',
                'module_file' => UploadedFile::fake()
                    ->create('unsafe.exe', 32, 'application/octet-stream'),
                'is_published' => '1',
            ])
            ->assertRedirect(route('trainer.resources'))
            ->assertSessionHasErrors('module_file');

        $module->refresh();
        $this->assertSame('Safe Patient Transfer', $module->title);
        $this->assertSame($originalPath, $module->file_path);
        Storage::disk('local')->assertExists($originalPath);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_draft_module_is_not_listed_or_openable_by_a_trainee(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch, [
            'title' => 'Trainer draft module',
            'is_published' => false,
            'published_at' => null,
        ]);

        $this->actingAs($trainee)
            ->get(route('trainee.modules.index'))
            ->assertOk()
            ->assertDontSee('Trainer draft module');

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $module))
            ->assertNotFound();
    }

    public function test_direct_content_access_creates_and_touches_module_progress(): void
    {
        Storage::fake('local');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch);
        Storage::disk('local')->put($module->file_path, '%PDF protected lesson');

        $this->actingAs($trainee)
            ->get(route('trainee.modules.content', $module))
            ->assertOk();

        $progress = ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->where('training_module_id', $module->id)
            ->firstOrFail();

        $this->assertSame(ModuleProgress::STATUS_IN_PROGRESS, $progress->status);
        $this->assertSame(10, $progress->progress_percent);
        $this->assertNotNull($progress->first_opened_at);
        $this->assertNotNull($progress->last_viewed_at);
    }
}
