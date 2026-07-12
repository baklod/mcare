<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_batches', function (Blueprint $table) {
            $table->dateTime('training_starts_at')->nullable()->after('enrollment_ends_at');
            $table->dateTime('training_ends_at')->nullable()->after('training_starts_at');
            $table->index(['training_starts_at', 'training_ends_at']);
        });

        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->foreignId('payment_verified_by_id')->nullable()->after('payment_meta')->constrained('users')->nullOnDelete();
            $table->dateTime('payment_verified_at')->nullable()->after('payment_verified_by_id');
            $table->text('payment_verification_notes')->nullable()->after('payment_verified_at');
            $table->index(['payment_method', 'payment_status', 'training_batch_id'], 'payment_verification_queue_index');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->dropIndex('payment_verification_queue_index');
            $table->dropConstrainedForeignId('payment_verified_by_id');
            $table->dropColumn(['payment_verified_at', 'payment_verification_notes']);
        });

        Schema::table('training_batches', function (Blueprint $table) {
            $table->dropIndex(['training_starts_at', 'training_ends_at']);
            $table->dropColumn(['training_starts_at', 'training_ends_at']);
        });
    }
};
