<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('career_opportunities', function (Blueprint $table) {
            $table->date('estimated_start_date')->nullable()->after('created_by_id');
            $table->string('patient_gender', 24)->nullable()->after('estimated_start_date');
            $table->string('mobility_status', 24)->nullable()->after('patient_gender');
            $table->unsignedTinyInteger('patient_age')->nullable()->after('mobility_status');
            $table->string('specific_contraptions', 255)->nullable()->after('patient_age');
            $table->string('condition_summary', 500)->nullable()->after('specific_contraptions');
            $table->index(
                ['is_published', 'estimated_start_date'],
                'career_opportunities_public_start_index'
            );
        });

        // Existing broad listings require an admin privacy review before republication.
        DB::table('career_opportunities')->update([
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function down(): void
    {
        Schema::table('career_opportunities', function (Blueprint $table) {
            $table->dropIndex('career_opportunities_public_start_index');
            $table->dropColumn([
                'estimated_start_date',
                'patient_gender',
                'mobility_status',
                'patient_age',
                'specific_contraptions',
                'condition_summary',
            ]);
        });
    }
};
