<?php

use App\Support\CaregivingNcIiCatalog;
use Database\Seeders\CaregivingCompetencySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        (new CaregivingCompetencySeeder)->run();
    }

    public function down(): void
    {
        DB::table('competency_units')
            ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
            ->delete();
    }
};
