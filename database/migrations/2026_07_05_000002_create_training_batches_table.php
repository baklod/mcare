<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->unsignedSmallInteger('year');
            $table->boolean('is_active')->default(false);
            $table->dateTime('enrollment_starts_at')->nullable();
            $table->dateTime('enrollment_ends_at');
            $table->time('am_start_time')->nullable();
            $table->time('am_end_time')->nullable();
            $table->string('am_room', 120)->nullable();
            $table->time('pm_start_time')->nullable();
            $table->time('pm_end_time')->nullable();
            $table->string('pm_room', 120)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['name', 'year']);
            $table->index(['is_active', 'enrollment_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_batches');
    }
};
