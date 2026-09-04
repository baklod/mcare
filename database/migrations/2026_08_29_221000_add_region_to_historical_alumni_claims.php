<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('historical_alumni_claims', 'region')) {
            Schema::table('historical_alumni_claims', function (Blueprint $table) {
                $table->string('region', 120)->nullable()->after('province');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('historical_alumni_claims', 'region')) {
            Schema::table('historical_alumni_claims', function (Blueprint $table) {
                $table->dropColumn('region');
            });
        }
    }
};
