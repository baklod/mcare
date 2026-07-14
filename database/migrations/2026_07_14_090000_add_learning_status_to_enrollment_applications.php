<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->string('learning_status', 30)->default('active')->after('status');
            $table->text('learning_status_notes')->nullable()->after('learning_status');
            $table->timestamp('learning_status_changed_at')->nullable()->after('learning_status_notes');
            $table->foreignId('learning_status_changed_by_id')
                ->nullable()
                ->after('learning_status_changed_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('learning_status_changed_by_id');
            $table->dropColumn([
                'learning_status',
                'learning_status_notes',
                'learning_status_changed_at',
            ]);
        });
    }
};
