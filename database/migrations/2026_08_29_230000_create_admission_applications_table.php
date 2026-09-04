<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number', 24)->unique();
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('email', 255);
            $table->string('contact_number', 30);
            $table->string('schedule_preference', 20)->nullable();
            $table->string('educational_attainment', 150)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('training_program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('program', 120)->default('Caregiving NC II');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('privacy_consent_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_applications');
    }
};
