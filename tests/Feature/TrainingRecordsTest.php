<?php

namespace Tests\Feature;

use App\Contracts\OfficialDocumentRenderer;
use App\Jobs\GenerateBatchTorExport;
use App\Jobs\GenerateOfficialDocument;
use App\Models\BatchDocumentExport;
use App\Models\CompetencyUnit;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\OfficialDocument;
use App\Models\TraineeCompetencyRecord;
use App\Models\TraineeOutcomeResult;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\User;
use App\Services\CompletionEligibilityService;
use App\Services\CompetencyWorkbookExporter;
use App\Services\OfficialDocumentManager;
use App\Support\CaregivingNcIiCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;
use ZipArchive;

class TrainingRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_competency_catalog_is_available_after_migration(): void
    {
        $this->assertDatabaseCount('competency_units', 24);
        $this->assertSame(11, CompetencyUnit::query()->where('is_tor_included', true)->count());
        $this->assertDatabaseHas('competency_units', [
            'program_code' => CaregivingNcIiCatalog::PROGRAM_CODE,
            'code' => 'HCS323301',
            'title' => 'Provide Care and Support to Infants and Toddlers',
        ]);
    }

    public function test_trainer_can_record_outcomes_and_official_grade(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->approvedApplication($trainee);
        $unit = CompetencyUnit::query()->with('outcomes')->orderBy('sort_order')->firstOrFail();

        $payload = [
            'unit_id' => $unit->id,
            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
            'percentage_score' => 95,
            'notes' => 'Observed and verified during the practical session.',
            'outcomes' => $unit->outcomes->mapWithKeys(
                fn ($outcome) => [$outcome->id => TraineeCompetencyRecord::STATUS_COMPETENT]
            )->all(),
        ];

        $this->actingAs($trainer)
            ->patch(route('trainer.competencies.update', $application), [
                'records' => [$unit->id => $payload],
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('trainee_competency_records', [
            'enrollment_application_id' => $application->id,
            'competency_unit_id' => $unit->id,
            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
            'tor_grade' => 1.30,
        ]);
        $this->assertSame($unit->outcomes->count(), TraineeOutcomeResult::query()->count());
    }

    public function test_completion_requires_every_core_unit_module_and_achievement_outcome(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->approvedApplication($trainee, completed: true);
        $this->completeCompetencies($application, $trainer);

        $eligibility = app(CompletionEligibilityService::class)->evaluate($application->fresh('batch'));

        $this->assertTrue($eligibility['eligible']);
        $this->assertSame(11, $eligibility['counts']['competent_units']);
        $this->assertGreaterThan(24, $eligibility['counts']['competent_outcomes']);

        TraineeOutcomeResult::query()
            ->whereHas('outcome.unit', fn ($query) => $query
                ->where('category', TrainingModule::CATEGORY_CORE))
            ->firstOrFail()
            ->update([
            'status' => TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT,
        ]);

        $this->assertFalse(app(CompletionEligibilityService::class)
            ->evaluate($application->fresh('batch'))['eligible']);
    }

    public function test_admin_can_queue_documents_only_after_completion(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->approvedApplication($trainee, completed: true);
        $this->completeCompetencies($application, $trainer);

        $this->actingAs($admin)
            ->post(route('admin.learning.documents.generate', [$application, 'cotc']))
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('official_documents', [
            'enrollment_application_id' => $application->id,
            'type' => OfficialDocument::TYPE_COTC,
            'status' => OfficialDocument::STATUS_QUEUED,
            'version' => 1,
        ]);
        Queue::assertPushed(GenerateOfficialDocument::class);
    }

    public function test_trainer_can_open_batch_progress_and_achievement_charts(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->approvedApplication($trainee);
        $this->completeCompetencies($application, $trainer);

        $this->actingAs($trainer)
            ->get(route('trainer.competencies.chart', [$application->batch, 'progress']))
            ->assertOk()
            ->assertSee('Progress Chart')
            ->assertSee('HCS323301');

        $this->actingAs($trainer)
            ->get(route('trainer.competencies.chart', [$application->batch, 'achievement']))
            ->assertOk()
            ->assertSee('Achievement Chart')
            ->assertSee('Provide Care and Support to Infants and Toddlers');
    }

    public function test_trainer_can_open_the_batch_grading_board(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->approvedApplication($trainee);

        $this->actingAs($trainer)
            ->get(route('trainer.competencies.index', ['batch_id' => $application->training_batch_id]))
            ->assertOk()
            ->assertSee('Batch grading board')
            ->assertSee('Bulk update')
            ->assertSee('data-competency-cell', false)
            ->assertSee($application->last_name.', '.$application->first_name);
    }

    public function test_trainer_and_admin_can_download_a_real_competency_workbook(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->approvedApplication($trainee);
        $this->completeCompetencies($application, $trainer);

        $export = app(CompetencyWorkbookExporter::class)->build($application->batch, 'AM');
        $reader = new Reader();
        $reader->open($export['path']);
        $sheetRows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            $sheetRows[$sheet->getName()] = collect(iterator_to_array($sheet->getRowIterator()))
                ->take(8)
                ->map(fn ($row) => $row->toArray())
                ->values()
                ->all();
        }

        $reader->close();
        @unlink($export['path']);

        $this->assertSame(['Progress Matrix', 'Achievement Outcomes', 'Legend'], array_keys($sheetRows));
        $this->assertSame('Trainee ID', $sheetRows['Progress Matrix'][5][0]);
        $this->assertStringContainsString('Trainee, Record', $sheetRows['Progress Matrix'][6][1]);
        $this->assertSame('C | 95%', $sheetRows['Progress Matrix'][6][5]);
        $this->assertSame('C', $sheetRows['Achievement Outcomes'][6][5]);

        $this->actingAs($trainer)
            ->get(route('trainer.competencies.export', [
                'trainingBatch' => $application->batch,
                'schedule' => 'AM',
            ]))
            ->assertOk()
            ->assertDownload();

        $this->actingAs($admin)
            ->get(route('admin.learning.competency-workbooks.download', [
                'batch_id' => $application->training_batch_id,
                'schedule' => 'AM',
            ]))
            ->assertOk()
            ->assertDownload();
    }

    public function test_trainer_can_bulk_update_selected_trainees_atomically(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $firstUser = User::factory()->create(['role' => 'trainee']);
        $secondUser = User::factory()->create(['role' => 'trainee']);
        $firstApplication = $this->approvedApplication($firstUser);
        $secondApplication = $firstApplication->replicate();
        $secondApplication->fill([
            'user_id' => $secondUser->id,
            'email' => $secondUser->email,
            'first_name' => 'Second',
            'last_name' => 'Trainee',
        ])->save();
        $unit = CompetencyUnit::query()->with('outcomes')->orderBy('sort_order')->firstOrFail();

        $this->actingAs($trainer)
            ->patch(route('trainer.competencies.bulk-update'), [
                'batch_id' => $firstApplication->training_batch_id,
                'unit_id' => $unit->id,
                'trainee_ids' => [$firstApplication->id, $secondApplication->id],
                'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
                'percentage_score' => 90,
                'notes' => 'Batch practical assessment completed.',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertSame(2, TraineeCompetencyRecord::query()
            ->where('competency_unit_id', $unit->id)
            ->where('status', TraineeCompetencyRecord::STATUS_COMPETENT)
            ->where('tor_grade', 1.75)
            ->count());
        $this->assertSame($unit->outcomes->count() * 2, TraineeOutcomeResult::query()->count());
    }

    public function test_bulk_update_rejects_a_trainee_from_another_batch_without_partial_changes(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $firstApplication = $this->approvedApplication(User::factory()->create(['role' => 'trainee']));
        $otherApplication = $this->approvedApplication(User::factory()->create(['role' => 'trainee']));
        $unit = CompetencyUnit::query()->orderBy('sort_order')->firstOrFail();

        $this->actingAs($trainer)
            ->from(route('trainer.competencies.index', ['batch_id' => $firstApplication->training_batch_id]))
            ->patch(route('trainer.competencies.bulk-update'), [
                'batch_id' => $firstApplication->training_batch_id,
                'unit_id' => $unit->id,
                'trainee_ids' => [$firstApplication->id, $otherApplication->id],
                'status' => TraineeCompetencyRecord::STATUS_IN_PROGRESS,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('trainee_ids');

        $this->assertDatabaseCount('trainee_competency_records', 0);
    }

    public function test_trainee_cotc_download_is_atomically_limited_to_one(): void
    {
        Storage::fake('local');
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->approvedApplication($trainee, completed: true);
        Storage::disk('local')->put('official-documents/cotc/test.pdf', '%PDF-1.4 test');
        $document = OfficialDocument::create([
            'enrollment_application_id' => $application->id,
            'training_batch_id' => $application->training_batch_id,
            'type' => OfficialDocument::TYPE_COTC,
            'version' => 1,
            'document_number' => 'MCARE-COTC-2026-00001-V1',
            'status' => OfficialDocument::STATUS_RELEASED,
            'storage_disk' => 'local',
            'file_path' => 'official-documents/cotc/test.pdf',
            'released_at' => now(),
        ]);

        $this->actingAs($trainee)
            ->get(route('trainee.cotc.download', $document))
            ->assertOk()
            ->assertDownload($document->document_number.'.pdf');

        $this->actingAs($trainee)
            ->get(route('trainee.cotc.download', $document))
            ->assertRedirect(route('trainee.documents'))
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('official_documents', [
            'id' => $document->id,
            'status' => OfficialDocument::STATUS_DOWNLOADED,
            'download_count' => 1,
        ]);
        $this->assertDatabaseCount('official_document_downloads', 1);
    }

    public function test_batch_tor_export_streams_a_unique_archive(): void
    {
        Storage::fake('local');
        $this->app->bind(OfficialDocumentRenderer::class, fn () => new class implements OfficialDocumentRenderer
        {
            public function render(OfficialDocument $document): string
            {
                return '%PDF-1.4 generated '.$document->document_number;
            }
        });
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->approvedApplication($trainee, completed: true);
        $this->completeCompetencies($application, $trainer);
        $export = BatchDocumentExport::create([
            'training_batch_id' => $application->training_batch_id,
            'type' => OfficialDocument::TYPE_TOR,
            'status' => BatchDocumentExport::STATUS_QUEUED,
            'storage_disk' => 'local',
            'requested_by_id' => $admin->id,
        ]);

        (new GenerateBatchTorExport($export->id))->handle(app(OfficialDocumentManager::class));

        $export->refresh();
        $this->assertSame(BatchDocumentExport::STATUS_READY, $export->status);
        Storage::disk('local')->assertExists($export->file_path);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('local')->path($export->file_path)));
        $this->assertStringContainsString('-'.$application->id.'-TOR.pdf', $zip->getNameIndex(0));
        $zip->close();
    }

    private function approvedApplication(User $trainee, bool $completed = false): EnrollmentApplication
    {
        $batch = TrainingBatch::create([
            'name' => 'Batch Records '.$trainee->id,
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->subMonths(6),
            'training_starts_at' => now()->subMonths(5),
            'training_ends_at' => $completed ? now()->subDay() : now()->addMonth(),
        ]);

        return EnrollmentApplication::create([
            'user_id' => $trainee->id,
            'training_batch_id' => $batch->id,
            'email' => $trainee->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Record',
            'last_name' => 'Trainee',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => '1 Training Street',
            'barangay' => 'Central',
            'city' => 'Pili',
            'province' => 'Camarines Sur',
            'zip_code' => '4418',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE School',
            'year_graduated' => 2022,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'learning_status' => EnrollmentApplication::LEARNING_ACTIVE,
            'payment_status' => EnrollmentApplication::PAYMENT_PAID,
            'reviewed_at' => now(),
            'learning_started_at' => now(),
        ]);
    }

    private function completeCompetencies(EnrollmentApplication $application, User $trainer): void
    {
        CompetencyUnit::query()->with('outcomes')->each(function ($unit) use ($application, $trainer): void {
            $record = TraineeCompetencyRecord::create([
                'enrollment_application_id' => $application->id,
                'competency_unit_id' => $unit->id,
                'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
                'percentage_score' => 95,
                'tor_grade' => 1.30,
                'assessed_by_id' => $trainer->id,
                'assessed_at' => now(),
            ]);

            foreach ($unit->outcomes as $outcome) {
                TraineeOutcomeResult::create([
                    'trainee_competency_record_id' => $record->id,
                    'competency_outcome_id' => $outcome->id,
                    'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
                    'assessed_by_id' => $trainer->id,
                    'assessed_at' => now(),
                ]);
            }

            if ($unit->category !== TrainingModule::CATEGORY_CORE || ! $unit->is_required) {
                return;
            }

            $module = TrainingModule::create([
                'trainer_id' => $trainer->id,
                'training_batch_id' => $application->training_batch_id,
                'module_code' => $unit->code,
                'competency_category' => TrainingModule::CATEGORY_CORE,
                'title' => $unit->title,
                'description' => 'Completed core competency delivery.',
                'file_path' => "training-modules/testing/{$unit->code}.pdf",
                'original_file_name' => "{$unit->code}.pdf",
                'is_published' => true,
                'delivery_status' => TrainingModule::DELIVERY_CLOSED,
                'published_at' => now()->subDay(),
                'activated_at' => now()->subDay(),
                'closed_at' => now(),
            ]);

            ModuleProgress::create([
                'enrollment_application_id' => $application->id,
                'training_module_id' => $module->id,
                'status' => ModuleProgress::STATUS_COMPLETED,
                'progress_percent' => 100,
                'assigned_at' => now()->subDay(),
                'unlocked_at' => now()->subDay(),
                'submitted_at' => now(),
                'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
                'evaluated_by_id' => $trainer->id,
                'evaluated_at' => now(),
                'completed_at' => now(),
            ]);
        });
    }

    public function test_admin_can_graduate_trainee_directly_and_fulfill_competencies_without_blocking(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->approvedApplication($trainee, completed: false);

        $this->actingAs($admin)
            ->patch(route('admin.learning.trainees.status', $application), [
                'learning_status' => 'graduated',
                'learning_status_notes' => 'Completed requirements through direct onsite evaluation.',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $application->refresh();
        $this->assertSame('graduated', $application->learning_status);

        // Competency records are marked Competent
        $compRecords = TraineeCompetencyRecord::where('enrollment_application_id', $application->id)->get();
        $this->assertNotEmpty($compRecords);
        $this->assertTrue($compRecords->every(fn ($r) => $r->status === 'competent'));

        // Trainee can open grades page with official notice
        $this->actingAs($trainee)
            ->get(route('trainee.grades'))
            ->assertOk()
            ->assertSee('Official Certificate and Transcript of Records (TOR) Notice');
    }
}
