<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('trainee_status', 30)->nullable()->after('applicant_status');
        });

        $applications = DB::table('enrollment_applications')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->get(['user_id', 'status', 'learning_status']);

        foreach ($applications as $application) {
            $status = $application->status === 'approved'
                ? ($application->learning_status ?: 'active')
                : null;

            DB::table('users')->where('id', $application->user_id)->update([
                'trainee_status' => $status,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('trainee_status');
        });
    }
};
