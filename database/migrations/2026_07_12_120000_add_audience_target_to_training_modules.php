<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_modules', function (Blueprint $table) {
            $table->foreignId('target_enrollment_application_id')
                ->nullable()
                ->after('training_batch_id')
                ->constrained('enrollment_applications')
                ->nullOnDelete();
            $table->index(
                ['target_enrollment_application_id', 'is_published'],
                'training_modules_target_published_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('training_modules', function (Blueprint $table) {
            $table->dropIndex('training_modules_target_published_index');
            $table->dropConstrainedForeignId('target_enrollment_application_id');
        });
    }
};
