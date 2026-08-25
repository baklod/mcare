<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_batches', function (Blueprint $table) {
            $table->boolean('is_continuous_enrollment')->default(false)->after('is_active');
            $table->dateTime('enrollment_ends_at')->nullable()->change();
        });

        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->timestamp('learning_started_at')->nullable()->after('reviewed_at');
            $table->index(['status', 'learning_started_at'], 'enrollment_learning_start_index');
        });

        Schema::table('training_modules', function (Blueprint $table) {
            $table->string('delivery_status', 20)->default('draft')->after('is_published');
            $table->timestamp('activated_at')->nullable()->after('published_at');
            $table->timestamp('closed_at')->nullable()->after('activated_at');
            $table->index(
                ['training_batch_id', 'target_enrollment_application_id', 'delivery_status'],
                'training_modules_delivery_index'
            );
        });

        Schema::table('module_progress', function (Blueprint $table) {
            $table->unsignedInteger('sequence_number')->nullable()->after('training_module_id');
            $table->timestamp('assigned_at')->nullable()->after('progress_percent');
            $table->timestamp('unlocked_at')->nullable()->after('assigned_at');
            $table->timestamp('submitted_at')->nullable()->after('unlocked_at');
            $table->index(
                ['enrollment_application_id', 'status', 'sequence_number'],
                'module_progress_assignment_index'
            );
        });

        $now = now();

        $latestActiveBatch = DB::table('training_batches')
            ->where('is_active', true)
            ->orderByDesc('training_starts_at')
            ->orderByDesc('id')
            ->first();

        if ($latestActiveBatch) {
            DB::table('training_batches')->where('id', $latestActiveBatch->id)->update([
                'is_continuous_enrollment' => true,
                'enrollment_ends_at' => null,
            ]);
        }

        DB::table('enrollment_applications')
            ->where('status', 'approved')
            ->whereNull('learning_started_at')
            ->update([
                'learning_started_at' => DB::raw('COALESCE(reviewed_at, updated_at, created_at)'),
            ]);

        DB::table('training_modules')->where('is_published', false)->update([
            'delivery_status' => 'draft',
            'activated_at' => null,
            'closed_at' => null,
        ]);

        DB::table('training_modules')->where('is_published', true)->update([
            'delivery_status' => 'closed',
            'closed_at' => $now,
        ]);

        $batchIds = DB::table('training_modules')
            ->where('is_published', true)
            ->whereNull('target_enrollment_application_id')
            ->whereNotNull('training_batch_id')
            ->distinct()
            ->pluck('training_batch_id');

        foreach ($batchIds as $batchId) {
            $activeModule = DB::table('training_modules')
                ->where('is_published', true)
                ->where('training_batch_id', $batchId)
                ->whereNull('target_enrollment_application_id')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->first();

            if ($activeModule) {
                DB::table('training_modules')->where('id', $activeModule->id)->update([
                    'delivery_status' => 'active',
                    'activated_at' => $activeModule->published_at ?: $activeModule->updated_at ?: $now,
                    'closed_at' => null,
                ]);
            }
        }

        DB::table('training_modules')
            ->where('is_published', true)
            ->whereNotNull('target_enrollment_application_id')
            ->update([
                'delivery_status' => 'active',
                'activated_at' => DB::raw('COALESCE(published_at, updated_at, created_at)'),
                'closed_at' => null,
            ]);

        $sequenceByApplication = [];
        $existingProgress = DB::table('module_progress')
            ->orderBy('enrollment_application_id')
            ->orderBy('id')
            ->get();

        foreach ($existingProgress as $progress) {
            $applicationId = (int) $progress->enrollment_application_id;
            $sequenceByApplication[$applicationId] = ($sequenceByApplication[$applicationId] ?? 0) + 1;
            $isCompetent = $progress->competency_outcome === 'competent';
            $status = match (true) {
                $isCompetent => 'completed',
                $progress->competency_outcome === 'not_yet_competent' => 'needs_remediation',
                $progress->status === 'completed' => 'awaiting_evaluation',
                default => $progress->status,
            };

            DB::table('module_progress')->where('id', $progress->id)->update([
                'sequence_number' => $sequenceByApplication[$applicationId],
                'status' => $status,
                'assigned_at' => $progress->created_at ?: $now,
                'unlocked_at' => $progress->created_at ?: $now,
                'submitted_at' => in_array($status, ['awaiting_evaluation', 'completed'], true)
                    ? ($progress->completed_at ?: $progress->updated_at ?: $now)
                    : null,
            ]);
        }

        $assign = function (object $module, int $applicationId) use (&$sequenceByApplication, $now): void {
            if (DB::table('module_progress')
                ->where('enrollment_application_id', $applicationId)
                ->where('training_module_id', $module->id)
                ->exists()) {
                return;
            }

            $hasBlockingAssignment = DB::table('module_progress')
                ->where('enrollment_application_id', $applicationId)
                ->where(function ($query): void {
                    $query->where('status', '!=', 'completed')
                        ->orWhereNull('competency_outcome')
                        ->orWhere('competency_outcome', '!=', 'competent');
                })
                ->exists();

            $sequenceByApplication[$applicationId] = ($sequenceByApplication[$applicationId] ?? 0) + 1;
            DB::table('module_progress')->insert([
                'enrollment_application_id' => $applicationId,
                'training_module_id' => $module->id,
                'sequence_number' => $sequenceByApplication[$applicationId],
                'status' => $hasBlockingAssignment ? 'locked' : 'not_started',
                'progress_percent' => 0,
                'assigned_at' => $now,
                'unlocked_at' => $hasBlockingAssignment ? null : $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        };

        $activeBatchModules = DB::table('training_modules')
            ->where('delivery_status', 'active')
            ->where('is_published', true)
            ->whereNull('target_enrollment_application_id')
            ->whereNotNull('training_batch_id')
            ->get();

        foreach ($activeBatchModules as $module) {
            $applicationIds = DB::table('enrollment_applications')
                ->where('training_batch_id', $module->training_batch_id)
                ->where('status', 'approved')
                ->where(function ($query): void {
                    $query->whereNull('learning_status')->orWhere('learning_status', 'active');
                })
                ->pluck('id');

            foreach ($applicationIds as $applicationId) {
                $assign($module, (int) $applicationId);
            }
        }

        $activePrivateModules = DB::table('training_modules')
            ->where('delivery_status', 'active')
            ->where('is_published', true)
            ->whereNotNull('target_enrollment_application_id')
            ->get();

        foreach ($activePrivateModules as $module) {
            $assign($module, (int) $module->target_enrollment_application_id);
        }
    }

    public function down(): void
    {
        Schema::table('module_progress', function (Blueprint $table) {
            $table->dropIndex('module_progress_assignment_index');
            $table->dropColumn(['sequence_number', 'assigned_at', 'unlocked_at', 'submitted_at']);
        });

        Schema::table('training_modules', function (Blueprint $table) {
            $table->dropIndex('training_modules_delivery_index');
            $table->dropColumn(['delivery_status', 'activated_at', 'closed_at']);
        });

        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->dropIndex('enrollment_learning_start_index');
            $table->dropColumn('learning_started_at');
        });

        DB::table('training_batches')->whereNull('enrollment_ends_at')->update([
            'enrollment_ends_at' => now()->addMonth(),
        ]);

        Schema::table('training_batches', function (Blueprint $table) {
            $table->dateTime('enrollment_ends_at')->nullable(false)->change();
            $table->dropColumn('is_continuous_enrollment');
        });
    }
};
