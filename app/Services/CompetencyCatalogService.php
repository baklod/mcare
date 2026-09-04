<?php

namespace App\Services;

use App\Models\CompetencyOutcome;
use App\Models\CompetencyUnit;
use App\Support\CaregivingNcIiCatalog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompetencyCatalogService
{
    public function caregivingUnits(bool $selectableOnly = false): Collection
    {
        return CompetencyUnit::query()
            ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
            ->when($selectableOnly, fn ($query) => $query->where('is_selectable', true))
            ->with(['outcomes' => fn ($query) => $query->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
    }

    /**
     * Units the trainer or admin has actually published as classwork for a batch,
     * arranged in admin catalog order inside each competency category.
     */
    public function unitsDeliveredForBatch(?int $batchId): Collection
    {
        if (! $batchId) {
            return CompetencyUnit::query()->whereRaw('1 = 0')->get();
        }

        $units = CompetencyUnit::query()
            ->with(['outcomes' => fn ($query) => $query->orderBy('sort_order')])
            ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
            ->where(function ($query) use ($batchId): void {
                $query->whereHas(
                    'trainingModules',
                    fn ($modules) => $modules
                        ->where('training_batch_id', $batchId)
                        ->where('is_published', true),
                )->orWhereExists(function ($exists) use ($batchId): void {
                    $exists->selectRaw('1')
                        ->from('training_modules')
                        ->whereColumn('training_modules.module_code', 'competency_units.code')
                        ->where('training_modules.training_batch_id', $batchId)
                        ->where('training_modules.is_published', true)
                        ->whereNotNull('training_modules.module_code')
                        ->where('training_modules.module_code', '!=', '');
                });
            })
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        $categoryOrder = [
            'core' => 0,
            'common' => 1,
            'basic' => 2,
            'custom' => 3,
        ];

        return $units
            ->sortBy(fn (CompetencyUnit $unit): array => [
                $categoryOrder[$unit->category] ?? 9,
                (int) $unit->sort_order,
                (string) $unit->code,
                (int) $unit->id,
            ])
            ->values();
    }

    /**
     * @return Collection<string, Collection<int, CompetencyUnit>>
     */
    public function groupByCategory(Collection $units): Collection
    {
        $grouped = new Collection;

        foreach (['core', 'common', 'basic', 'custom'] as $category) {
            $slice = $units->where('category', $category)->values();

            if ($slice->isNotEmpty()) {
                $grouped[$category] = $slice;
            }
        }

        return $grouped;
    }

    /**
     * @return list<string>
     */
    public function parseOutcomeLines(?string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $text) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values()
            ->all();
    }

    public function create(array $payload): CompetencyUnit
    {
        return DB::transaction(function () use ($payload): CompetencyUnit {
            $unit = CompetencyUnit::query()->create([
                'program_code' => CaregivingNcIiCatalog::PROGRAM_CODE,
                'category' => $payload['category'],
                'code' => $payload['code'],
                'title' => $payload['title'],
                'estimated_hours' => $payload['estimated_hours'] ?? null,
                'sort_order' => $this->nextSortOrder(),
                'is_required' => ($payload['category'] ?? '') !== 'custom',
                'is_tor_included' => filter_var($payload['is_tor_included'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_selectable' => filter_var($payload['is_selectable'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ]);

            $this->syncOutcomes($unit, $payload['outcomes'] ?? []);

            return $unit->load('outcomes');
        });
    }

    public function update(CompetencyUnit $unit, array $payload): CompetencyUnit
    {
        return DB::transaction(function () use ($unit, $payload): CompetencyUnit {
            $unit->fill([
                'category' => $payload['category'],
                'code' => $payload['code'],
                'title' => $payload['title'],
                'estimated_hours' => $payload['estimated_hours'] ?? null,
                'is_tor_included' => filter_var($payload['is_tor_included'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_selectable' => filter_var($payload['is_selectable'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ])->save();

            $this->syncOutcomes($unit, $payload['outcomes'] ?? []);

            return $unit->load('outcomes');
        });
    }

    public function deleteUnit(CompetencyUnit $unit): void
    {
        $this->assertUnitDeletable($unit);

        DB::transaction(function () use ($unit): void {
            $unit->outcomes()->delete();
            $unit->delete();
        });
    }

    public function createOutcome(array $payload): CompetencyOutcome
    {
        $unit = CompetencyUnit::query()->findOrFail($payload['competency_unit_id']);

        return DB::transaction(function () use ($unit, $payload): CompetencyOutcome {
            return CompetencyOutcome::query()->create([
                'competency_unit_id' => $unit->id,
                'title' => $payload['title'],
                'sort_order' => $this->nextOutcomeSortOrder($unit->id),
                'is_required' => filter_var($payload['is_required'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ]);
        });
    }

    public function updateOutcome(CompetencyOutcome $outcome, array $payload): CompetencyOutcome
    {
        return DB::transaction(function () use ($outcome, $payload): CompetencyOutcome {
            $unitId = (int) ($payload['competency_unit_id'] ?? $outcome->competency_unit_id);
            $sortOrder = (int) $outcome->sort_order;

            if ($unitId !== (int) $outcome->competency_unit_id) {
                $sortOrder = $this->nextOutcomeSortOrder($unitId);
            }

            $outcome->fill([
                'competency_unit_id' => $unitId,
                'title' => $payload['title'],
                'sort_order' => $sortOrder,
                'is_required' => filter_var($payload['is_required'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ])->save();

            return $outcome->fresh('unit') ?? $outcome;
        });
    }

    public function deleteOutcome(CompetencyOutcome $outcome): void
    {
        if ($outcome->traineeResults()->exists()) {
            throw ValidationException::withMessages([
                'outcome' => 'This outcome has trainee results and cannot be deleted.',
            ]);
        }

        if ($outcome->submodules()->exists()) {
            throw ValidationException::withMessages([
                'outcome' => 'This outcome is used by a classwork submodule and cannot be deleted.',
            ]);
        }

        $outcome->delete();
    }

    private function assertUnitDeletable(CompetencyUnit $unit): void
    {
        if ($unit->trainingModules()->exists()) {
            throw ValidationException::withMessages([
                'unit' => 'This unit is used by a learning module and cannot be deleted.',
            ]);
        }

        if ($unit->traineeRecords()->exists()) {
            throw ValidationException::withMessages([
                'unit' => 'This unit has trainee competency records and cannot be deleted.',
            ]);
        }

        $outcomeIds = $unit->outcomes()->pluck('id');

        if ($outcomeIds->isEmpty()) {
            return;
        }

        if (CompetencyOutcome::query()
            ->whereIn('id', $outcomeIds)
            ->where(fn ($query) => $query
                ->whereHas('traineeResults')
                ->orWhereHas('submodules'))
            ->exists()) {
            throw ValidationException::withMessages([
                'unit' => 'This unit has outcomes used in classwork or trainee records and cannot be deleted.',
            ]);
        }
    }

    private function nextOutcomeSortOrder(int $unitId): int
    {
        return (int) CompetencyOutcome::query()
            ->where('competency_unit_id', $unitId)
            ->max('sort_order') + 1;
    }

    /**
     * @param  list<string>  $titles
     */
    private function syncOutcomes(CompetencyUnit $unit, array $titles): void
    {
        $titles = collect($titles)
            ->map(fn ($title) => trim((string) $title))
            ->filter()
            ->values();

        foreach ($titles as $index => $title) {
            CompetencyOutcome::query()->updateOrCreate(
                [
                    'competency_unit_id' => $unit->id,
                    'sort_order' => $index + 1,
                ],
                [
                    'title' => $title,
                    'is_required' => true,
                ],
            );
        }

        $unit->outcomes()
            ->where('sort_order', '>', $titles->count())
            ->whereDoesntHave('traineeResults')
            ->delete();
    }

    private function nextSortOrder(): int
    {
        return (int) CompetencyUnit::query()
            ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
            ->max('sort_order') + 1;
    }
}
