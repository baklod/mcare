<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('career_opportunities', function (Blueprint $table) {
            $table->string('sms_mode', 20)->default('none')->after('published_at');
            $table->dateTime('sms_scheduled_at')->nullable()->after('sms_mode');
            $table->dateTime('sms_sent_at')->nullable()->after('sms_scheduled_at');
            $table->unsignedInteger('sms_sent_count')->default(0)->after('sms_sent_at');
            $table->unsignedInteger('sms_skipped_count')->default(0)->after('sms_sent_count');
        });
    }

    public function down(): void
    {
        Schema::table('career_opportunities', function (Blueprint $table) {
            $table->dropColumn([
                'sms_mode',
                'sms_scheduled_at',
                'sms_sent_at',
                'sms_sent_count',
                'sms_skipped_count',
            ]);
        });
    }
};
