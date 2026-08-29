<?php

use App\Models\EnrollmentApplication;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('training_programs')) {
            Schema::create('training_programs', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 120);
                $table->string('code', 50)->unique();
                $table->text('description')->nullable();
                $table->decimal('total_program_fee', 10, 2)->default(22000.00);
                $table->decimal('downpayment_amount', 10, 2)->default(2000.00);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $now = now();
        $defaultProgramId = DB::table('training_programs')
            ->where('code', 'CAREGIVING-NC-II')
            ->value('id');

        if (! $defaultProgramId) {
            $defaultProgramId = DB::table('training_programs')->insertGetId([
                'name' => 'Caregiving NC II',
                'code' => 'CAREGIVING-NC-II',
                'description' => 'MCARE Caregiving NC II training program.',
                'total_program_fee' => 22000.00,
                'downpayment_amount' => 2000.00,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! Schema::hasColumn('training_batches', 'training_program_id')) {
            Schema::table('training_batches', function (Blueprint $table): void {
                $table->foreignId('training_program_id')->nullable()->after('id');
            });
        }

        if (! Schema::hasColumn('training_batches', 'show_on_enrollment_page')) {
            Schema::table('training_batches', function (Blueprint $table): void {
                $table->boolean('show_on_enrollment_page')->default(false)->after('is_active');
            });
        }

        if (! $this->hasForeignKeyForColumn('training_batches', 'training_program_id')) {
            Schema::table('training_batches', function (Blueprint $table): void {
                $table->foreign('training_program_id', 'training_batches_program_fk')
                    ->references('id')
                    ->on('training_programs')
                    ->restrictOnDelete();
            });
        }

        if (! Schema::hasIndex('training_batches', 'training_batches_public_active_idx')) {
            Schema::table('training_batches', function (Blueprint $table): void {
                $table->index(
                    ['show_on_enrollment_page', 'is_active'],
                    'training_batches_public_active_idx',
                );
            });
        }

        DB::table('training_batches')
            ->whereNull('training_program_id')
            ->update([
                'training_program_id' => $defaultProgramId,
                // Preserve the public behavior of batches that were already active.
                'show_on_enrollment_page' => DB::raw('is_active'),
            ]);

        if (Schema::hasIndex('training_batches', 'training_batches_name_year_unique')) {
            Schema::table('training_batches', function (Blueprint $table): void {
                $table->dropUnique('training_batches_name_year_unique');
            });
        }

        if (! Schema::hasIndex('training_batches', 'training_batches_program_name_year_unique')) {
            Schema::table('training_batches', function (Blueprint $table): void {
                $table->unique(
                    ['training_program_id', 'name', 'year'],
                    'training_batches_program_name_year_unique',
                );
            });
        }

        if (! Schema::hasColumn('enrollment_applications', 'training_program_id')) {
            Schema::table('enrollment_applications', function (Blueprint $table): void {
                $table->foreignId('training_program_id')->nullable()->after('program');
            });
        }

        if (! Schema::hasColumn('enrollment_applications', 'review_released_at')) {
            Schema::table('enrollment_applications', function (Blueprint $table): void {
                $table->timestamp('review_released_at')->nullable()->after('status');
            });
        }

        if (! $this->hasForeignKeyForColumn('enrollment_applications', 'training_program_id')) {
            Schema::table('enrollment_applications', function (Blueprint $table): void {
                $table->foreign('training_program_id', 'enroll_app_program_fk')
                    ->references('id')
                    ->on('training_programs')
                    ->restrictOnDelete();
            });
        }

        if (! Schema::hasIndex('enrollment_applications', 'enroll_app_review_released_idx')) {
            Schema::table('enrollment_applications', function (Blueprint $table): void {
                $table->index('review_released_at', 'enroll_app_review_released_idx');
            });
        }

        DB::table('training_batches')
            ->select(['id', 'training_program_id'])
            ->orderBy('id')
            ->each(function (object $batch): void {
                DB::table('enrollment_applications')
                    ->where('training_batch_id', $batch->id)
                    ->whereNull('training_program_id')
                    ->update(['training_program_id' => $batch->training_program_id]);
            });

        // Reviewed history must remain visible. Unreviewed records are released
        // only when the verified downpayment requirement has been satisfied.
        DB::table('enrollment_applications')
            ->whereNull('review_released_at')
            ->where(function ($query): void {
                $query->whereIn('status', [
                    EnrollmentApplication::STATUS_APPROVED,
                    EnrollmentApplication::STATUS_DENIED,
                ])->orWhere(function ($payment): void {
                    $payment->whereNotNull('payment_verified_at')
                        ->whereColumn('total_paid_amount', '>=', 'downpayment_amount');
                });
            })
            ->update(['review_released_at' => DB::raw('COALESCE(payment_verified_at, reviewed_at, updated_at)')]);
    }

    public function down(): void
    {
        if (Schema::hasIndex('enrollment_applications', 'enroll_app_review_released_idx')) {
            Schema::table('enrollment_applications', function (Blueprint $table): void {
                $table->dropIndex('enroll_app_review_released_idx');
            });
        }

        if ($this->hasForeignKeyForColumn('enrollment_applications', 'training_program_id')) {
            Schema::table('enrollment_applications', function (Blueprint $table): void {
                $table->dropForeign('enroll_app_program_fk');
            });
        }

        $applicationColumns = collect(['training_program_id', 'review_released_at'])
            ->filter(fn (string $column): bool => Schema::hasColumn('enrollment_applications', $column))
            ->all();
        if ($applicationColumns !== []) {
            Schema::table('enrollment_applications', function (Blueprint $table) use ($applicationColumns): void {
                $table->dropColumn($applicationColumns);
            });
        }

        if (Schema::hasIndex('training_batches', 'training_batches_program_name_year_unique')) {
            Schema::table('training_batches', function (Blueprint $table): void {
                $table->dropUnique('training_batches_program_name_year_unique');
            });
        }

        if (Schema::hasIndex('training_batches', 'training_batches_public_active_idx')) {
            Schema::table('training_batches', function (Blueprint $table): void {
                $table->dropIndex('training_batches_public_active_idx');
            });
        }

        if ($this->hasForeignKeyForColumn('training_batches', 'training_program_id')) {
            Schema::table('training_batches', function (Blueprint $table): void {
                $table->dropForeign('training_batches_program_fk');
            });
        }

        $batchColumns = collect(['training_program_id', 'show_on_enrollment_page'])
            ->filter(fn (string $column): bool => Schema::hasColumn('training_batches', $column))
            ->all();
        if ($batchColumns !== []) {
            Schema::table('training_batches', function (Blueprint $table) use ($batchColumns): void {
                $table->dropColumn($batchColumns);
            });
        }

        $hasDuplicateLegacyBatches = DB::table('training_batches')
            ->select(['name', 'year'])
            ->groupBy(['name', 'year'])
            ->havingRaw('COUNT(*) > 1')
            ->exists();
        if (! $hasDuplicateLegacyBatches && ! Schema::hasIndex('training_batches', 'training_batches_name_year_unique')) {
            Schema::table('training_batches', function (Blueprint $table): void {
                $table->unique(['name', 'year']);
            });
        }

        Schema::dropIfExists('training_programs');
    }

    private function hasForeignKeyForColumn(string $table, string $column): bool
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return false;
        }

        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if (in_array($column, $foreignKey['columns'], true)) {
                return true;
            }
        }

        return false;
    }
};
