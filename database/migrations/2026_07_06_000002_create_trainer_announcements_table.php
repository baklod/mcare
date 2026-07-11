<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('training_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 160);
            $table->text('message');
            $table->string('audience', 40)->default('trainees');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['trainer_id', 'posted_at']);
            $table->index(['training_batch_id', 'audience']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_announcements');
    }
};
