<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quizzes') && ! Schema::hasColumn('quizzes', 'requires_time_in')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->boolean('requires_time_in')->default(false)->after('passing_score_percent');
            });
        }

        if (! Schema::hasTable('trainee_attendances')) {
            Schema::create('trainee_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_batch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('enrollment_application_id')
                    ->constrained('enrollment_applications')
                    ->cascadeOnDelete();
                $table->date('attendance_date');
                $table->foreignId('quiz_id')->nullable()->constrained('quizzes')->nullOnDelete();
                $table->string('status', 30)->default('present');
                $table->string('check_in_type', 40)->default('daily_sheet');
                $table->dateTime('timed_in_at')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(
                    ['training_batch_id', 'enrollment_application_id', 'attendance_date', 'quiz_id'],
                    'trainee_attendances_batch_date_quiz_unique'
                );
                $table->index(
                    ['training_batch_id', 'attendance_date'],
                    'trainee_attendances_batch_date_index'
                );
                $table->index(
                    ['enrollment_application_id', 'attendance_date'],
                    'trainee_attendances_trainee_date_index'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trainee_attendances');

        if (Schema::hasTable('quizzes') && Schema::hasColumn('quizzes', 'requires_time_in')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dropColumn('requires_time_in');
            });
        }
    }
};
