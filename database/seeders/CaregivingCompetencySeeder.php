<?php

namespace Database\Seeders;

use App\Support\CaregivingNcIiCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CaregivingCompetencySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (CaregivingNcIiCatalog::units() as $unitIndex => $definition) {
                $unitId = DB::table('competency_units')->updateOrInsert(
                    [
                        'program_code' => CaregivingNcIiCatalog::PROGRAM_CODE,
                        'title' => $definition['title'],
                    ],
                    [
                        'category' => $definition['category'],
                        'code' => $definition['code'],
                        'sort_order' => $unitIndex + 1,
                        'is_required' => true,
                        'is_tor_included' => $definition['tor'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );

                $unitId = DB::table('competency_units')
                    ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
                    ->where('title', $definition['title'])
                    ->value('id');

                foreach ($definition['outcomes'] as $outcomeIndex => $outcomeTitle) {
                    DB::table('competency_outcomes')->updateOrInsert(
                        [
                            'competency_unit_id' => $unitId,
                            'sort_order' => $outcomeIndex + 1,
                        ],
                        [
                            'title' => $outcomeTitle,
                            'is_required' => true,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ],
                    );
                }
            }
        });
    }
}
