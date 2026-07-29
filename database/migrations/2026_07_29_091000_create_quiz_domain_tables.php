<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('training_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('target_enrollment_application_id')
                ->nullable()
                ->constrained('enrollment_applications')
                ->nullOnDelete();
            $table->string('title', 160);
            $table->text('instructions')->nullable();
            $table->boolean('is_published')->default(false);
            $table->dateTime('published_at')->nullable();
            $table->dateTime('available_at')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->unsignedSmallInteger('time_limit_minutes')->nullable();
            $table->unsignedSmallInteger('attempt_limit')->default(1);
            $table->decimal('passing_score_percent', 5, 2)->default(75);
            $table->timestamps();

            $table->index(
                ['training_batch_id', 'is_published', 'available_at'],
                'quizzes_batch_publication_index'
            );
            $table->index(
                ['target_enrollment_application_id', 'is_published'],
                'quizzes_target_publication_index'
            );
            $table->index(['trainer_id', 'updated_at'], 'quizzes_trainer_updated_index');
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->default('multiple_choice');
            $table->text('prompt');
            $table->json('options');
            // Option indexes are zero-based so they map directly to the stored JSON array.
            $table->unsignedSmallInteger('correct_option');
            $table->decimal('points', 8, 2)->default(1);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['quiz_id', 'position'], 'quiz_questions_order_index');
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_application_id')
                ->constrained('enrollment_applications')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number')->default(1);
            $table->string('status', 30)->default('in_progress');
            // Answers are stored as {"question_id": zero_based_option_index}.
            $table->json('answers')->nullable();
            $table->decimal('earned_points', 10, 2)->nullable();
            $table->decimal('total_points', 10, 2)->nullable();
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->boolean('passed')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('graded_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['quiz_id', 'enrollment_application_id', 'attempt_number'],
                'quiz_attempts_quiz_trainee_number_unique'
            );
            $table->index(
                ['enrollment_application_id', 'status'],
                'quiz_attempts_trainee_status_index'
            );
            $table->index(['quiz_id', 'status'], 'quiz_attempts_quiz_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
    }
};
