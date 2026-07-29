<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_modules', function (Blueprint $table) {
            $table->string('topic', 120)->nullable();
            $table->dateTime('available_at')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->unsignedInteger('position')->default(0);

            $table->index(
                ['training_batch_id', 'is_published', 'available_at', 'position'],
                'training_modules_classwork_index'
            );
        });

        Schema::table('trainer_announcements', function (Blueprint $table) {
            $table->string('kind', 30)->default('announcement');
            $table->boolean('is_pinned')->default(false);
            // Existing announcement records remain visible after this additive migration.
            $table->boolean('is_published')->default(true);
            $table->dateTime('expires_at')->nullable();

            $table->index(
                ['training_batch_id', 'is_published', 'posted_at'],
                'trainer_announcements_stream_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('trainer_announcements', function (Blueprint $table) {
            $table->dropIndex('trainer_announcements_stream_index');
            $table->dropColumn([
                'kind',
                'is_pinned',
                'is_published',
                'expires_at',
            ]);
        });

        Schema::table('training_modules', function (Blueprint $table) {
            $table->dropIndex('training_modules_classwork_index');
            $table->dropColumn([
                'topic',
                'available_at',
                'due_at',
                'position',
            ]);
        });
    }
};
