<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competency_units', function (Blueprint $table) {
            $table->id();
            $table->string('program_code', 80)->index();
            $table->string('category', 20)->index();
            $table->string('code', 40)->nullable();
            $table->string('title');
            $table->unsignedSmallInteger('sort_order');
            $table->boolean('is_required')->default(true);
            $table->boolean('is_tor_included')->default(false);
            $table->timestamps();

            $table->unique(['program_code', 'title'], 'competency_units_program_title_unique');
            $table->unique(['program_code', 'sort_order'], 'competency_units_program_order_unique');
        });

        Schema::create('competency_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competency_unit_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('sort_order');
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(
                ['competency_unit_id', 'sort_order'],
                'competency_outcomes_unit_order_unique'
            );
        });

        Schema::create('trainee_competency_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_unit_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('not_assessed');
            $table->decimal('percentage_score', 5, 2)->nullable();
            $table->decimal('tor_grade', 4, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assessed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['enrollment_application_id', 'competency_unit_id'],
                'trainee_competency_records_application_unit_unique'
            );
            $table->index(
                ['enrollment_application_id', 'status'],
                'trainee_records_application_status_index'
            );
        });

        Schema::create('trainee_outcome_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainee_competency_record_id')
                ->constrained('trainee_competency_records')
                ->cascadeOnDelete();
            $table->foreignId('competency_outcome_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('not_assessed');
            $table->foreignId('assessed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['trainee_competency_record_id', 'competency_outcome_id'],
                'trainee_outcome_results_record_outcome_unique'
            );
        });

        Schema::create('official_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->index();
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('document_number', 80)->unique();
            $table->string('status', 30)->default('queued')->index();
            $table->string('storage_disk', 40)->default('local');
            $table->string('file_path')->nullable();
            $table->string('sha256', 64)->nullable();
            $table->string('template_version', 30)->default('1.0');
            $table->foreignId('generated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('released_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->unsignedSmallInteger('download_count')->default(0);
            $table->timestamp('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->text('generation_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                ['enrollment_application_id', 'type', 'version'],
                'official_documents_application_type_version_unique'
            );
            $table->index(
                ['enrollment_application_id', 'type', 'status'],
                'official_documents_application_type_status_index'
            );
        });

        Schema::create('official_document_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('official_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_role', 30);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('downloaded_at');
            $table->timestamps();

            $table->index(
                ['official_document_id', 'downloaded_at'],
                'official_downloads_document_date_index'
            );
        });

        Schema::create('batch_document_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_batch_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('tor');
            $table->string('status', 30)->default('queued')->index();
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('processed_records')->default(0);
            $table->string('storage_disk', 40)->default('local');
            $table->string('file_path')->nullable();
            $table->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(
                ['training_batch_id', 'type', 'created_at'],
                'batch_exports_batch_type_created_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_document_exports');
        Schema::dropIfExists('official_document_downloads');
        Schema::dropIfExists('official_documents');
        Schema::dropIfExists('trainee_outcome_results');
        Schema::dropIfExists('trainee_competency_records');
        Schema::dropIfExists('competency_outcomes');
        Schema::dropIfExists('competency_units');
    }
};
