<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('target_type')->default('all'); // all, batch, user
            $table->foreignId('training_batch_id')->nullable()->constrained('training_batches')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('kind')->default('reminder'); // announcement, reminder, news
            $table->date('due_date')->nullable();
            $table->boolean('send_email')->default(false);
            $table->boolean('is_published')->default(true);
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'is_published']);
            $table->index(['training_batch_id', 'is_published']);
            $table->index(['target_user_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_announcements');
    }
};
