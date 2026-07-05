<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_batches', function (Blueprint $table) {
            $table->string('am_days', 50)->default('MWF')->after('am_room');
            $table->string('pm_days', 50)->default('TTS')->after('pm_room');
        });
    }

    public function down(): void
    {
        Schema::table('training_batches', function (Blueprint $table) {
            $table->dropColumn(['am_days', 'pm_days']);
        });
    }
};
