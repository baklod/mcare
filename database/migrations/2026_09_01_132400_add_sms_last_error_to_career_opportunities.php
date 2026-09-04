<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('career_opportunities', function (Blueprint $table) {
            $table->text('sms_last_error')->nullable()->after('sms_skipped_count');
        });

        // Earlier sends could be marked complete after a Semaphore validation error.
        DB::table('career_opportunities')
            ->whereNotNull('sms_sent_at')
            ->where('sms_sent_count', '>', 0)
            ->update([
                'sms_sent_at' => null,
                'sms_sent_count' => 0,
                'sms_last_error' => 'Retry needed. Confirm an approved Semaphore sender name is configured.',
            ]);
    }

    public function down(): void
    {
        Schema::table('career_opportunities', function (Blueprint $table) {
            $table->dropColumn('sms_last_error');
        });
    }
};
