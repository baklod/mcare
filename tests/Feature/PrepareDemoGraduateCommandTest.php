<?php

namespace Tests\Feature;

use App\Contracts\OfficialDocumentRenderer;
use App\Models\EnrollmentApplication;
use App\Models\OfficialDocument;
use App\Models\TraineeCompetencyRecord;
use App\Models\User;
use App\Services\CompletionEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrepareDemoGraduateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_prepares_an_idempotent_completed_trainee_with_downloadable_documents(): void
    {
        Storage::fake('local');
        Queue::fake();
        $this->app->bind(OfficialDocumentRenderer::class, fn () => new class implements OfficialDocumentRenderer
        {
            public function render(OfficialDocument $document): string
            {
                return '%PDF-1.4 demo '.$document->document_number;
            }
        });
        User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'trainer']);
        $email = 'demo.completed.test@gmail.com';

        $this->artisan('mcare:prepare-demo-graduate', [
            '--email' => $email,
            '--password' => 'DemoPass!2026',
        ])->assertSuccessful();

        $user = User::query()->where('email', $email)->firstOrFail();
        $application = EnrollmentApplication::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('trainee', $user->role);
        $this->assertTrue(app(CompletionEligibilityService::class)->evaluate($application->fresh('batch'))['eligible']);
        $this->assertSame(24, TraineeCompetencyRecord::query()
            ->where('enrollment_application_id', $application->id)
            ->where('status', TraineeCompetencyRecord::STATUS_COMPETENT)
            ->count());
        $this->assertDatabaseHas('official_documents', [
            'enrollment_application_id' => $application->id,
            'type' => OfficialDocument::TYPE_COTC,
            'status' => OfficialDocument::STATUS_RELEASED,
        ]);
        $this->assertDatabaseHas('official_documents', [
            'enrollment_application_id' => $application->id,
            'type' => OfficialDocument::TYPE_TOR,
            'status' => OfficialDocument::STATUS_GENERATED,
        ]);

        $documents = OfficialDocument::query()
            ->where('enrollment_application_id', $application->id)
            ->get();
        $this->assertCount(2, $documents);
        $documents->each(fn ($document) => Storage::disk('local')->assertExists($document->file_path));

        $this->artisan('mcare:prepare-demo-graduate', [
            '--email' => $email,
            '--password' => 'DemoPass!2026',
        ])->assertSuccessful();

        $this->assertSame(2, OfficialDocument::query()
            ->where('enrollment_application_id', $application->id)
            ->count());
    }
}
