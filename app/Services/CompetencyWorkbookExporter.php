<?php

namespace App\Services;

use App\Models\CompetencyUnit;
use App\Models\EnrollmentApplication;
use App\Models\TraineeCompetencyRecord;
use App\Models\TrainingBatch;
use App\Support\CaregivingNcIiCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Throwable;

class CompetencyWorkbookExporter
{
    /**
     * @return array{path: string, filename: string, trainee_count: int}
     */
    public function build(TrainingBatch $batch, ?string $schedule = null): array
    {
        $units = CompetencyUnit::query()
            ->with('outcomes')
            ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
            ->where('is_required', true)
            ->orderBy('sort_order')
            ->get();
        $outcomes = $units->flatMap(fn ($unit) => $unit->outcomes)->values();
        $traineeCount = $this->traineeQuery($batch, $schedule)->count();
        $path = tempnam(sys_get_temp_dir(), 'mcare-competencies-');

        if ($path === false) {
            throw new \RuntimeException('A temporary Excel export file could not be created.');
        }

        $options = new Options();
        // Shared strings render reliably in Microsoft Excel, LibreOffice, Apple Numbers, and mobile previews.
        $options->SHOULD_USE_INLINE_STRINGS = false;
        $options->mergeCells(0, 1, max(4, $units->count() + 4), 1, 0);
        $options->mergeCells(0, 1, max(4, $outcomes->count() + 4), 1, 1);
        $options->mergeCells(0, 1, 3, 1, 2);
        $writer = new Writer($options);
        $opened = false;

        try {
            $writer->openToFile($path);
            $opened = true;
            $writer->setCreator('Mission Care Training Center');
            $this->writeProgressSheet($writer, $batch, $schedule, $units);
            $this->writeAchievementSheet($writer, $batch, $schedule, $units, $outcomes);
            $this->writeLegendSheet($writer, $units);
            $writer->close();
            $opened = false;
        } catch (Throwable $exception) {
            if ($opened) {
                $writer->close();
            }

            @unlink($path);

            throw $exception;
        }

        $batchSlug = Str::slug($batch->name.'-'.$batch->year) ?: 'batch-'.$batch->id;
        $classSlug = $schedule ? '-'.strtolower($schedule) : '';

        return [
            'path' => $path,
            'filename' => 'MCARE-'.$batchSlug.$classSlug.'-competency-records-'.now()->format('Ymd-His').'.xlsx',
            'trainee_count' => $traineeCount,
        ];
    }

    private function writeProgressSheet(
        Writer $writer,
        TrainingBatch $batch,
        ?string $schedule,
        Collection $units,
    ): void {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Progress Matrix');
        $sheet->setSheetView((new SheetView())
            ->setFreezeRow(7)
            ->setFreezeColumn('F')
            ->setZoomScale(85));
        $sheet->setColumnWidth(16, 1);
        $sheet->setColumnWidth(28, 2);
        $sheet->setColumnWidth(32, 3);
        $sheet->setColumnWidth(12, 4);
        $sheet->setColumnWidth(14, 5);
        $sheet->setColumnWidthForRange(16, 6, max(6, $units->count() + 5));

        $this->writeSheetHeading($writer, 'MCARE Caregiving NC II Competency Progress', $batch, $schedule);
        $writer->addRow(Row::fromValues(
            ['Trainee ID', 'Trainee', 'Gmail account', 'Class', 'Completion', ...$units->map(
                fn ($unit) => ($unit->code ?: 'U'.str_pad((string) $unit->sort_order, 2, '0', STR_PAD_LEFT)).' | '.$unit->title
            )->all()],
            $this->headerStyle(),
        ));

        foreach ($this->traineeQuery($batch, $schedule)->lazy(100) as $trainee) {
            $records = $trainee->competencyRecords->keyBy('competency_unit_id');
            $competent = $records->where('status', TraineeCompetencyRecord::STATUS_COMPETENT)->count();
            $values = [
                'MCARE-TRN-'.str_pad((string) $trainee->id, 5, '0', STR_PAD_LEFT),
                trim($trainee->last_name.', '.$trainee->first_name.' '.$trainee->middle_name),
                $trainee->email,
                $trainee->schedule_preference ?: '-',
                $units->isNotEmpty() ? $competent / $units->count() : 0,
            ];
            $columnStyles = [4 => $this->percentageStyle()];

            foreach ($units as $index => $unit) {
                $record = $records->get($unit->id);
                $values[] = $this->recordLabel($record);
                $columnStyles[$index + 5] = $this->statusStyle($record?->status);
            }

            $writer->addRow(Row::fromValuesWithStyles($values, null, $columnStyles));
        }
    }

    private function writeAchievementSheet(
        Writer $writer,
        TrainingBatch $batch,
        ?string $schedule,
        Collection $units,
        Collection $outcomes,
    ): void {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Achievement Outcomes');
        $sheet->setSheetView((new SheetView())
            ->setFreezeRow(7)
            ->setFreezeColumn('F')
            ->setZoomScale(75));
        $sheet->setColumnWidth(16, 1);
        $sheet->setColumnWidth(28, 2);
        $sheet->setColumnWidth(32, 3);
        $sheet->setColumnWidth(12, 4);
        $sheet->setColumnWidth(14, 5);
        $sheet->setColumnWidthForRange(24, 6, max(6, $outcomes->count() + 5));

        $this->writeSheetHeading($writer, 'MCARE Caregiving NC II Achievement Outcomes', $batch, $schedule);
        $writer->addRow(Row::fromValues(
            ['Trainee ID', 'Trainee', 'Gmail account', 'Class', 'Completion', ...$units->flatMap(function ($unit) {
                $code = $unit->code ?: 'U'.str_pad((string) $unit->sort_order, 2, '0', STR_PAD_LEFT);

                return $unit->outcomes->map(fn ($outcome) => $code.' | '.$outcome->title);
            })->all()],
            $this->headerStyle(),
        ));

        foreach ($this->traineeQuery($batch, $schedule)->lazy(100) as $trainee) {
            $results = $trainee->competencyRecords
                ->flatMap(fn ($record) => $record->outcomeResults)
                ->keyBy('competency_outcome_id');
            $competentOutcomes = $results->where('status', TraineeCompetencyRecord::STATUS_COMPETENT)->count();
            $values = [
                'MCARE-TRN-'.str_pad((string) $trainee->id, 5, '0', STR_PAD_LEFT),
                trim($trainee->last_name.', '.$trainee->first_name.' '.$trainee->middle_name),
                $trainee->email,
                $trainee->schedule_preference ?: '-',
                $outcomes->isNotEmpty() ? $competentOutcomes / $outcomes->count() : 0,
            ];
            $columnStyles = [4 => $this->percentageStyle()];

            foreach ($outcomes as $index => $outcome) {
                $status = $results->get($outcome->id)?->status;
                $values[] = $this->statusSymbol($status);
                $columnStyles[$index + 5] = $this->statusStyle($status);
            }

            $writer->addRow(Row::fromValuesWithStyles($values, null, $columnStyles));
        }
    }

    private function writeLegendSheet(Writer $writer, Collection $units): void
    {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Legend');
        $sheet->setColumnWidth(18, 1);
        $sheet->setColumnWidth(30, 2);
        $sheet->setColumnWidth(70, 3);
        $sheet->setColumnWidth(18, 4);
        $writer->addRow($this->titleRow('MCARE Competency Workbook Guide'));
        $writer->addRow(Row::fromValues(['Purpose', 'Read-only progress snapshot for trainer, admin, trainee updates, and review.']));
        $writer->addRow(Row::fromValues(['Important', 'Update official records inside MCARE. Editing this file does not change the database.'], $this->noticeStyle()));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues(['Symbol', 'Meaning', 'Usage'], $this->headerStyle()));
        $writer->addRow(Row::fromValuesWithStyles(['C', 'Competent', 'A passing score and all required outcomes are competent.'], null, [0 => $this->statusStyle('competent')]));
        $writer->addRow(Row::fromValuesWithStyles(['IP', 'In progress', 'Training or assessment is still underway.'], null, [0 => $this->statusStyle('in_progress')]));
        $writer->addRow(Row::fromValuesWithStyles(['NYC', 'Not yet competent', 'The trainee needs reassessment or additional evidence.'], null, [0 => $this->statusStyle('not_yet_competent')]));
        $writer->addRow(Row::fromValuesWithStyles(['-', 'Not assessed', 'No official result has been recorded.'], null, [0 => $this->statusStyle(null)]));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues(['Code', 'Category', 'Competency unit', 'TOR included'], $this->headerStyle()));

        foreach ($units as $unit) {
            $writer->addRow(Row::fromValues([
                $unit->code ?: 'U'.str_pad((string) $unit->sort_order, 2, '0', STR_PAD_LEFT),
                str($unit->category)->headline()->toString(),
                $unit->title,
                $unit->is_tor_included ? 'Yes' : 'No',
            ]));
        }
    }

    private function writeSheetHeading(
        Writer $writer,
        string $title,
        TrainingBatch $batch,
        ?string $schedule,
    ): void {
        $writer->addRow($this->titleRow($title));
        $writer->addRow(Row::fromValues(['Batch', $batch->name.' '.$batch->year], $this->metadataStyle()));
        $writer->addRow(Row::fromValues(['Class', $schedule ?: 'AM and PM'], $this->metadataStyle()));
        $writer->addRow(Row::fromValues(['Generated', now()->format('Y-m-d h:i A').' ('.config('app.timezone').')'], $this->metadataStyle()));
        $writer->addRow(Row::fromValues(['Status', 'Read-only export. Update official records inside MCARE.'], $this->noticeStyle()));
    }

    private function traineeQuery(TrainingBatch $batch, ?string $schedule): Builder
    {
        return EnrollmentApplication::query()
            ->with(['competencyRecords.outcomeResults'])
            ->where('training_batch_id', $batch->id)
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->when($schedule, fn ($query, $class) => $query->where('schedule_preference', $class))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id');
    }

    private function recordLabel(?TraineeCompetencyRecord $record): string
    {
        if (! $record) {
            return '-';
        }

        $symbol = $this->statusSymbol($record->status);

        return $record->percentage_score !== null
            ? $symbol.' | '.number_format((float) $record->percentage_score, 0).'%'
            : $symbol;
    }

    private function statusSymbol(?string $status): string
    {
        return match ($status) {
            TraineeCompetencyRecord::STATUS_COMPETENT => 'C',
            TraineeCompetencyRecord::STATUS_IN_PROGRESS => 'IP',
            TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT => 'NYC',
            default => '-',
        };
    }

    private function titleRow(string $title): Row
    {
        $row = Row::fromValues([$title], (new Style())
            ->setFontBold()
            ->setFontSize(16)
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor('6D28D9')
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER));
        $row->setHeight(28);

        return $row;
    }

    private function headerStyle(): Style
    {
        return (new Style())
            ->setFontBold()
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor('4C1D95')
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->setShouldWrapText();
    }

    private function metadataStyle(): Style
    {
        return (new Style())->setBackgroundColor('F5F3FF');
    }

    private function noticeStyle(): Style
    {
        return (new Style())
            ->setFontBold()
            ->setFontColor('78350F')
            ->setBackgroundColor('FFFBEB')
            ->setShouldWrapText();
    }

    private function percentageStyle(): Style
    {
        return (new Style())
            ->setFontBold()
            ->setFormat('0%')
            ->setCellAlignment(CellAlignment::CENTER);
    }

    private function statusStyle(?string $status): Style
    {
        [$background, $font] = match ($status) {
            TraineeCompetencyRecord::STATUS_COMPETENT => ['DCFCE7', '166534'],
            TraineeCompetencyRecord::STATUS_IN_PROGRESS => ['FEF3C7', '92400E'],
            TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT => ['FEE2E2', '991B1B'],
            default => ['F1F5F9', '475569'],
        };

        return (new Style())
            ->setFontBold()
            ->setFontColor($font)
            ->setBackgroundColor($background)
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }
}
