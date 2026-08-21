<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_batches', function (Blueprint $table): void {
            $table->foreignId('trainer_id')
                ->nullable()
                ->after('year')
                ->constrained('users')
                ->nullOnDelete();
            $table->index(['trainer_id', 'is_active'], 'training_batches_trainer_active_index');
        });

        // Preserve an existing trainer's class when the old prototype already
        // has modules, announcements, or quizzes tied to one trainer only.
        $batchIds = DB::table('training_batches')
            ->whereNull('trainer_id')
            ->pluck('id');

        foreach ($batchIds as $batchId) {
            $trainerIds = collect();

            foreach (['training_modules', 'trainer_announcements', 'quizzes'] as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $trainerIds = $trainerIds->merge(
                    DB::table($table)
                        ->where('training_batch_id', $batchId)
                        ->whereNotNull('trainer_id')
                        ->pluck('trainer_id')
                );
            }

            $trainerIds = $trainerIds->filter()->unique()->values();

            if ($trainerIds->count() === 1) {
                DB::table('training_batches')
                    ->where('id', $batchId)
                    ->whereNull('trainer_id')
                    ->update(['trainer_id' => $trainerIds->first()]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('training_batches', function (Blueprint $table): void {
            $table->dropIndex('training_batches_trainer_active_index');
            $table->dropConstrainedForeignId('trainer_id');
        });
    }
};
