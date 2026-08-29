<?php

use App\Models\EnrollmentApplication;
use App\Models\User;
use App\Notifications\LmsQuizPublished;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications') || ! Schema::hasTable('enrollment_applications')) {
            return;
        }

        $graduateUserIds = DB::table('enrollment_applications')
            ->where('learning_status', EnrollmentApplication::LEARNING_GRADUATED)
            ->whereNotNull('user_id')
            ->pluck('user_id');

        if ($graduateUserIds->isEmpty()) {
            return;
        }

        DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('type', LmsQuizPublished::class)
            ->whereIn('notifiable_id', $graduateUserIds)
            ->delete();
    }

    public function down(): void
    {
        // Removed notifications cannot be reconstructed safely.
    }
};
