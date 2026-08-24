<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('enrollment_applications')
            ->where('status', 'profile_submitted')
            ->update(['status' => 'pre_enlistment']);

        DB::table('users')
            ->where('applicant_status', 'profile_submitted')
            ->update(['applicant_status' => 'pre_enlistment']);
    }

    public function down(): void
    {
        // This display-status normalization intentionally keeps records at pre-enlistment.
    }
};
