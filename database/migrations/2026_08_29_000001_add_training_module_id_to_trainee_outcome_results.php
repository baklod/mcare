<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainee_outcome_results', function (Blueprint $table): void {
            $table->foreignId('training_module_id')
                ->nullable()
                ->after('trainee_competency_record_id')
                ->constrained('training_modules')
                ->nullOnDelete();
            $table->index(
                ['training_module_id', 'status'],
                'trainee_outcome_results_module_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('trainee_outcome_results', function (Blueprint $table): void {
            $table->dropIndex('trainee_outcome_results_module_status_index');
            $table->dropConstrainedForeignId('training_module_id');
        });
    }
};
