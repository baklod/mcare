<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasIntakeChannel = Schema::hasColumn('enrollment_applications', 'intake_channel');
        $hasHistoricalRecord = Schema::hasColumn('enrollment_applications', 'is_historical_record');
        $hasVerifiedAt = Schema::hasColumn('enrollment_applications', 'onsite_requirements_verified_at');
        $hasVerifiedBy = Schema::hasColumn('enrollment_applications', 'onsite_requirements_verified_by_id');
        $hasNotes = Schema::hasColumn('enrollment_applications', 'onsite_requirements_notes');

        Schema::table('enrollment_applications', function (Blueprint $table) use (
            $hasIntakeChannel,
            $hasHistoricalRecord,
            $hasVerifiedAt,
            $hasVerifiedBy,
            $hasNotes
        ) {
            if (! $hasIntakeChannel) {
                $table->string('intake_channel', 40)->default('online')->after('program')->index();
            }

            if (! $hasHistoricalRecord) {
                $table->boolean('is_historical_record')->default(false)->after('intake_channel')->index();
            }

            if (! $hasVerifiedAt) {
                $table->timestamp('onsite_requirements_verified_at')->nullable()->after('documents_reviewed_at');
            }

            if (! $hasVerifiedBy) {
                $table->foreignId('onsite_requirements_verified_by_id')
                    ->nullable()
                    ->after('onsite_requirements_verified_at');
                $table->foreign(
                    'onsite_requirements_verified_by_id',
                    'enroll_app_onsite_verified_by_fk'
                )->references('id')->on('users')->nullOnDelete();
            }

            if (! $hasNotes) {
                $table->text('onsite_requirements_notes')->nullable()->after('onsite_requirements_verified_by_id');
            }
        });

        if ($hasVerifiedBy && ! $this->hasForeignKeyForColumn(
            'enrollment_applications',
            'onsite_requirements_verified_by_id'
        )) {
            Schema::table('enrollment_applications', function (Blueprint $table) {
                $table->foreign(
                    'onsite_requirements_verified_by_id',
                    'enroll_app_onsite_verified_by_fk'
                )->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('historical_alumni_claims')) {
            Schema::create('historical_alumni_claims', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('first_name', 100);
                $table->string('middle_name', 100)->nullable();
                $table->string('last_name', 100);
                $table->date('birth_date');
                $table->string('gender', 40);
                $table->string('contact_number', 30);
                $table->string('street', 180);
                $table->string('barangay', 120);
                $table->string('city', 120);
                $table->string('province', 120);
                $table->string('zip_code', 20);
                $table->string('educational_attainment', 150);
                $table->string('school_name', 180);
                $table->unsignedSmallInteger('education_year_graduated');
                $table->unsignedSmallInteger('training_completion_year');
                $table->string('historical_batch_name', 120)->nullable();
                $table->string('training_schedule', 20)->nullable();
                $table->string('evidence_type', 40);
                $table->string('certificate_number', 120)->nullable();
                $table->string('tor_reference', 120)->nullable();
                $table->string('evidence_document_path')->nullable();
                $table->string('evidence_document_page_2_path')->nullable();
                $table->string('status', 40)->default('pending_email');
                $table->timestamp('privacy_consent_at');
                $table->json('verification_checks')->nullable();
                $table->timestamp('onsite_verified_at')->nullable();
                $table->foreignId('onsite_verified_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('admin_notes')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'created_at']);
                $table->index(['training_completion_year', 'historical_batch_name'], 'historical_alumni_training_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('historical_alumni_claims');

        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->dropForeign('enroll_app_onsite_verified_by_fk');
            $table->dropIndex(['intake_channel']);
            $table->dropIndex(['is_historical_record']);
            $table->dropColumn([
                'intake_channel',
                'is_historical_record',
                'onsite_requirements_verified_at',
                'onsite_requirements_verified_by_id',
                'onsite_requirements_notes',
            ]);
        });
    }

    private function hasForeignKeyForColumn(string $table, string $column): bool
    {
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if (in_array($column, $foreignKey['columns'], true)) {
                return true;
            }
        }

        return false;
    }
};
