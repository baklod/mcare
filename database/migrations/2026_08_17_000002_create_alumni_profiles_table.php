<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recover cleanly when MySQL created the table before an index-name failure.
        if (Schema::hasTable('alumni_profiles')) {
            if (! Schema::hasIndex('alumni_profiles', 'alumni_profiles_availability_idx')) {
                Schema::table('alumni_profiles', function (Blueprint $table) {
                    $table->index(
                        ['is_available_for_duty', 'availability_updated_at'],
                        'alumni_profiles_availability_idx'
                    );
                });
            }

            return;
        }

        Schema::create('alumni_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('is_available_for_duty')->default(false);
            $table->timestamp('availability_updated_at')->nullable();
            $table->timestamps();

            $table->index(
                ['is_available_for_duty', 'availability_updated_at'],
                'alumni_profiles_availability_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_profiles');
    }
};
