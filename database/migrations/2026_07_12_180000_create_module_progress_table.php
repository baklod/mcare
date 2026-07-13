<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recover safely when the table was created before Laravel recorded the migration.
        if (Schema::hasTable('module_progress')) {
            if (! Schema::hasIndex(
                'module_progress',
                ['enrollment_application_id', 'training_module_id'],
                'unique'
            )) {
                Schema::table('module_progress', function (Blueprint $table) {
                    $table->unique(
                        ['enrollment_application_id', 'training_module_id'],
                        'module_progress_app_module_unique'
                    );
                });
            }

            if (! Schema::hasIndex('module_progress', ['training_module_id', 'status'])) {
                Schema::table('module_progress', function (Blueprint $table) {
                    $table->index(
                        ['training_module_id', 'status'],
                        'module_progress_module_status_index'
                    );
                });
            }

            return;
        }

        Schema::create('module_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_module_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('not_started');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['enrollment_application_id', 'training_module_id'],
                'module_progress_app_module_unique'
            );
            $table->index(
                ['training_module_id', 'status'],
                'module_progress_module_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_progress');
    }
};
