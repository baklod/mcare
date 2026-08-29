<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('training_modules', 'completion_mode')) {
            Schema::table('training_modules', function (Blueprint $table): void {
                $table->string('completion_mode', 30)
                    ->default('assessed')
                    ->after('competency_category');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('training_modules', 'completion_mode')) {
            Schema::table('training_modules', function (Blueprint $table): void {
                $table->dropColumn('completion_mode');
            });
        }
    }
};
