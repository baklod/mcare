<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competency_units', function (Blueprint $table) {
            $table->unsignedSmallInteger('estimated_hours')->nullable()->after('title');
            $table->boolean('is_selectable')->default(true)->after('is_tor_included');
        });

        DB::table('competency_units')->where('category', 'core')->update(['estimated_hours' => 40]);
        DB::table('competency_units')->where('category', 'common')->update(['estimated_hours' => 20]);
        DB::table('competency_units')->where('category', 'basic')->update(['estimated_hours' => 18]);
    }

    public function down(): void
    {
        Schema::table('competency_units', function (Blueprint $table) {
            $table->dropColumn(['estimated_hours', 'is_selectable']);
        });
    }
};
