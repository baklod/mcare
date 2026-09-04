<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->foreignId('admission_application_id')
                ->nullable()
                ->after('user_id')
                ->constrained('admission_applications')
                ->nullOnDelete();
            $table->unique('admission_application_id');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admission_application_id');
        });
    }
};
