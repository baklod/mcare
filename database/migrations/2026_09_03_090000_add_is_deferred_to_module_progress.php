<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('module_progress')) {
            return;
        }

        if (! Schema::hasColumn('module_progress', 'is_deferred')) {
            Schema::table('module_progress', function (Blueprint $table) {
                $table->boolean('is_deferred')
                    ->default(false)
                    ->after('sequence_number');
            });
        }

        if (! Schema::hasIndex('module_progress', 'module_progress_deferred_idx')) {
            Schema::table('module_progress', function (Blueprint $table) {
                $table->index(
                    ['enrollment_application_id', 'is_deferred'],
                    'module_progress_deferred_idx',
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('module_progress')) {
            return;
        }

        if (Schema::hasIndex('module_progress', 'module_progress_deferred_idx')) {
            Schema::table('module_progress', function (Blueprint $table) {
                $table->dropIndex('module_progress_deferred_idx');
            });
        }

        if (Schema::hasColumn('module_progress', 'is_deferred')) {
            Schema::table('module_progress', function (Blueprint $table) {
                $table->dropColumn('is_deferred');
            });
        }
    }
};
