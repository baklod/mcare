<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_modules', function (Blueprint $table) {
            $table->string('release_mode', 24)->default('rolling')->after('completion_mode');
            $table->foreignId('competency_unit_id')
                ->nullable()
                ->after('competency_category')
                ->constrained('competency_units')
                ->nullOnDelete();
            $table->index(['training_batch_id', 'release_mode', 'delivery_status'], 'modules_batch_release_status_index');
        });

        Schema::create('training_submodules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_outcome_id')
                ->nullable()
                ->constrained('competency_outcomes')
                ->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['training_module_id', 'position'], 'submodules_module_position_unique');
            $table->unique(['training_module_id', 'competency_outcome_id'], 'submodules_module_outcome_unique');
        });

        Schema::create('training_submodule_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_submodule_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('not_started');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('quiz_score', 5, 2)->nullable();
            $table->string('practical_rating', 30)->nullable();
            $table->string('competency_outcome', 30)->nullable();
            $table->text('evaluation_remarks')->nullable();
            $table->foreignId('evaluated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['enrollment_application_id', 'training_submodule_id'],
                'submodule_progress_application_submodule_unique'
            );
            $table->index(
                ['training_submodule_id', 'status'],
                'submodule_progress_submodule_status_index'
            );
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreignId('training_submodule_id')
                ->nullable()
                ->after('training_module_id')
                ->constrained('training_submodules')
                ->nullOnDelete();
            $table->index(['training_submodule_id', 'is_published'], 'quizzes_submodule_publication_index');
        });

        $now = now();

        $activeCustomModules = DB::table('training_modules')
            ->where('competency_category', 'custom')
            ->where('delivery_status', 'active')
            ->whereNull('target_enrollment_application_id')
            ->get();

        DB::table('training_modules')
            ->where('competency_category', 'custom')
            ->update(['release_mode' => 'supplemental']);

        DB::table('training_modules')
            ->where('competency_category', 'custom')
            ->where('is_published', true)
            ->update([
                'delivery_status' => 'available',
                'closed_at' => null,
            ]);

        foreach ($activeCustomModules as $customModule) {
            $previousRolling = DB::table('training_modules')
                ->where('training_batch_id', $customModule->training_batch_id)
                ->whereNull('target_enrollment_application_id')
                ->where('competency_category', '!=', 'custom')
                ->where('is_published', true)
                ->where('delivery_status', 'closed')
                ->orderByDesc('activated_at')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->first();

            if ($previousRolling) {
                DB::table('training_modules')->where('id', $previousRolling->id)->update([
                    'delivery_status' => 'active',
                    'closed_at' => null,
                ]);
            }
        }

        $modules = DB::table('training_modules')->orderBy('id')->get();

        foreach ($modules as $module) {
            $unit = null;

            if (filled($module->module_code)) {
                $unit = DB::table('competency_units')
                    ->where('program_code', 'caregiving_nc_ii')
                    ->where('code', $module->module_code)
                    ->first();
            }

            if (! $unit && $module->competency_category === 'custom' && $module->completion_mode !== 'material_only') {
                $title = trim((string) $module->title);
                $existingTitle = DB::table('competency_units')
                    ->where('program_code', 'caregiving_nc_ii')
                    ->where('title', $title)
                    ->exists();

                if ($existingTitle) {
                    $title .= ' (Custom '.$module->id.')';
                }

                $sortOrder = ((int) DB::table('competency_units')
                    ->where('program_code', 'caregiving_nc_ii')
                    ->max('sort_order')) + 1;
                $unitId = DB::table('competency_units')->insertGetId([
                    'program_code' => 'caregiving_nc_ii',
                    'category' => 'custom',
                    'code' => filled($module->module_code) ? $module->module_code : 'MCARE-CUSTOM-'.$module->id,
                    'title' => $title,
                    'sort_order' => $sortOrder,
                    'is_required' => false,
                    'is_tor_included' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('competency_outcomes')->insert([
                    'competency_unit_id' => $unitId,
                    'title' => filled($module->topic) ? $module->topic : $module->title,
                    'sort_order' => 1,
                    'is_required' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $unit = DB::table('competency_units')->where('id', $unitId)->first();
            }

            if ($unit) {
                DB::table('training_modules')->where('id', $module->id)->update([
                    'competency_unit_id' => $unit->id,
                ]);

                $outcomes = DB::table('competency_outcomes')
                    ->where('competency_unit_id', $unit->id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();

                foreach ($outcomes as $index => $outcome) {
                    DB::table('training_submodules')->insert([
                        'training_module_id' => $module->id,
                        'competency_outcome_id' => $outcome->id,
                        'title' => $outcome->title,
                        'description' => null,
                        'position' => $index + 1,
                        'is_required' => (bool) $outcome->is_required,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            } else {
                DB::table('training_submodules')->insert([
                    'training_module_id' => $module->id,
                    'competency_outcome_id' => null,
                    'title' => filled($module->topic) ? $module->topic : $module->title,
                    'description' => null,
                    'position' => 1,
                    'is_required' => $module->completion_mode !== 'material_only',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $parentProgressRows = DB::table('module_progress')->orderBy('id')->get();

        foreach ($parentProgressRows as $parentProgress) {
            $module = DB::table('training_modules')->where('id', $parentProgress->training_module_id)->first();
            if (! $module) {
                continue;
            }

            $submodules = DB::table('training_submodules')
                ->where('training_module_id', $module->id)
                ->orderBy('position')
                ->get();

            foreach ($submodules as $submodule) {
                $outcomeResult = null;
                if ($submodule->competency_outcome_id && $module->competency_unit_id) {
                    $outcomeResult = DB::table('trainee_outcome_results as result')
                        ->join('trainee_competency_records as record', 'record.id', '=', 'result.trainee_competency_record_id')
                        ->where('record.enrollment_application_id', $parentProgress->enrollment_application_id)
                        ->where('record.competency_unit_id', $module->competency_unit_id)
                        ->where('result.competency_outcome_id', $submodule->competency_outcome_id)
                        ->select('result.*')
                        ->first();
                }

                $status = match ($outcomeResult?->status) {
                    'competent' => 'completed',
                    'not_yet_competent' => 'needs_remediation',
                    'in_progress' => 'in_progress',
                    default => match ($parentProgress->status) {
                        'completed' => 'completed',
                        'needs_remediation' => 'needs_remediation',
                        'awaiting_evaluation' => 'awaiting_evaluation',
                        'in_progress' => 'in_progress',
                        default => 'not_started',
                    },
                };
                $childOutcome = match ($status) {
                    'completed' => 'competent',
                    'needs_remediation' => 'not_yet_competent',
                    default => 'in_progress',
                };

                DB::table('training_submodule_progress')->insert([
                    'enrollment_application_id' => $parentProgress->enrollment_application_id,
                    'training_submodule_id' => $submodule->id,
                    'status' => $status,
                    'progress_percent' => match ($status) {
                        'completed' => 100,
                        'awaiting_evaluation' => 95,
                        'needs_remediation' => 75,
                        'in_progress' => max(10, min(90, (int) $parentProgress->progress_percent)),
                        default => 0,
                    },
                    'first_opened_at' => $parentProgress->first_opened_at,
                    'last_viewed_at' => $parentProgress->last_viewed_at,
                    'submitted_at' => in_array($status, ['awaiting_evaluation', 'completed', 'needs_remediation'], true)
                        ? ($parentProgress->submitted_at ?: $parentProgress->updated_at)
                        : null,
                    'quiz_score' => $parentProgress->quiz_score,
                    'practical_rating' => $parentProgress->practical_rating,
                    'competency_outcome' => $childOutcome,
                    'evaluation_remarks' => $parentProgress->evaluation_remarks,
                    'evaluated_by_id' => $outcomeResult?->assessed_by_id ?: $parentProgress->evaluated_by_id,
                    'evaluated_at' => $outcomeResult?->assessed_at ?: $parentProgress->evaluated_at,
                    'completed_at' => $status === 'completed' ? ($parentProgress->completed_at ?: $parentProgress->evaluated_at) : null,
                    'created_at' => $parentProgress->created_at ?: $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropIndex('quizzes_submodule_publication_index');
            $table->dropConstrainedForeignId('training_submodule_id');
        });

        Schema::dropIfExists('training_submodule_progress');
        Schema::dropIfExists('training_submodules');

        Schema::table('training_modules', function (Blueprint $table) {
            $table->dropIndex('modules_batch_release_status_index');
            $table->dropConstrainedForeignId('competency_unit_id');
            $table->dropColumn('release_mode');
        });
    }
};
