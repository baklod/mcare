<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classroom_comments', function (Blueprint $table): void {
            $table->id();
            $table->morphs('commentable');
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('training_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visibility', 20)->default('class');
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(
                ['commentable_type', 'commentable_id', 'visibility', 'created_at'],
                'classroom_comments_feed_index'
            );
            $table->index(
                ['recipient_user_id', 'visibility', 'created_at'],
                'classroom_comments_recipient_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_comments');
    }
};
