<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('module_progress')
            || ! Schema::hasColumn('module_progress', 'unlocked_at')) {
            return;
        }

        $activeAssignments = DB::table('module_progress')
            ->where('status', 'locked');

        if (Schema::hasTable('enrollment_applications')) {
            $activeAssignments->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('enrollment_applications')
                    ->whereColumn('enrollment_applications.id', 'module_progress.enrollment_application_id')
                    ->where('enrollment_applications.status', 'approved')
                    ->where(function ($applications): void {
                        $applications->whereNull('enrollment_applications.learning_status')
                            ->orWhere('enrollment_applications.learning_status', 'active');
                    })
                    ->where('enrollment_applications.is_historical_record', false);
            });
        }

        $activeAssignments->update([
            'status' => 'not_started',
            'unlocked_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Competency-based locks cannot be reconstructed safely after learners
        // have started newer modules, so rollback intentionally preserves access.
    }
};
