<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_modules', function (Blueprint $table) {
            $table->string('competency_category', 30)->nullable()->after('module_code');
            $table->unsignedSmallInteger('estimated_hours')->nullable()->after('topic');
            $table->json('supplementary_files')->nullable()->after('file_size');

            $table->index(['training_batch_id', 'competency_category'], 'training_modules_batch_category_index');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreignId('training_module_id')
                ->nullable()
                ->after('target_enrollment_application_id')
                ->constrained('training_modules')
                ->nullOnDelete();

            $table->index(
                ['training_module_id', 'is_published'],
                'quizzes_module_publication_index'
            );
        });

        Schema::table('module_progress', function (Blueprint $table) {
            $table->decimal('quiz_score', 5, 2)->nullable()->after('progress_percent');
            $table->string('practical_rating', 30)->nullable()->after('quiz_score');
            $table->string('competency_outcome', 30)->nullable()->after('practical_rating');
            $table->text('evaluation_remarks')->nullable()->after('competency_outcome');
            $table->foreignId('evaluated_by_id')->nullable()->after('evaluation_remarks')->constrained('users')->nullOnDelete();
            $table->timestamp('evaluated_at')->nullable()->after('evaluated_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('module_progress', function (Blueprint $table) {
            $table->dropForeign(['evaluated_by_id']);
            $table->dropColumn([
                'quiz_score',
                'practical_rating',
                'competency_outcome',
                'evaluation_remarks',
                'evaluated_by_id',
                'evaluated_at',
            ]);
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropIndex('quizzes_module_publication_index');
            $table->dropForeign(['training_module_id']);
            $table->dropColumn('training_module_id');
        });

        Schema::table('training_modules', function (Blueprint $table) {
            $table->dropIndex('training_modules_batch_category_index');
            $table->dropColumn([
                'competency_category',
                'estimated_hours',
                'supplementary_files',
            ]);
        });
    }
};
