<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_modules', function (Blueprint $table) {
            $table->string('module_code', 50)->nullable()->after('target_enrollment_application_id');
            $table->index(['training_batch_id', 'module_code']);
        });
    }

    public function down(): void
    {
        Schema::table('training_modules', function (Blueprint $table) {
            $table->dropIndex(['training_batch_id', 'module_code']);
            $table->dropColumn('module_code');
        });
    }
};
