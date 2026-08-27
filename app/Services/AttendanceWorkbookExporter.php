<?php

namespace App\Services;

use App\Models\EnrollmentApplication;
use App\Models\TraineeAttendance;
use App\Models\TrainingBatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
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

class AttendanceWorkbookExporter
{
    /**
     * @return array{path: string, filename: string, trainee_count: int}
     */
    public function build(TrainingBatch $batch, ?string $schedule = null): array
    {
        $trainees = $this->traineeQuery($batch, $schedule)->get();
        $traineeCount = $trainees->count();

        // Retrieve all unique recorded attendance dates for this batch
        $dates = TraineeAttendance::query()
            ->where('training_batch_id', $batch->id)
            ->whereNull('quiz_id')
            ->select('attendance_date')
            ->distinct()
            ->orderBy('attendance_date')
            ->pluck('attendance_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->values();

        $path = tempnam(sys_get_temp_dir(), 'mcare-attendance-');
        if ($path === false) {
            throw new \RuntimeException('A temporary Excel export file could not be created.');
        }

        $options = new Options;
        $options->SHOULD_USE_INLINE_STRINGS = false;
        $options->mergeCells(0, 1, max(4, $dates->count() + 8), 1, 0);
        $options->mergeCells(0, 1, 9, 1, 1);
        $options->mergeCells(0, 1, 3, 1, 2);
        $writer = new Writer($options);
        $opened = false;

        try {
            $writer->openToFile($path);
            $opened = true;
            $writer->setCreator('Mission Care Training Center');
            $this->writeMatrixSheet($writer, $batch, $schedule, $trainees, $dates);
            $this->writeComplianceSummarySheet($writer, $batch, $schedule, $trainees, $dates);
            $this->writeLegendSheet($writer);
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
            'filename' => 'MCARE-'.$batchSlug.$classSlug.'-attendance-roster-'.now()->format('Ymd-His').'.xlsx',
            'trainee_count' => $traineeCount,
        ];
    }

    /**
     * @param  Collection<int, EnrollmentApplication>  $trainees
     * @param  Collection<int, string>  $dates
     */
    private function writeMatrixSheet(
        Writer $writer,
        TrainingBatch $batch,
        ?string $schedule,
        Collection $trainees,
        Collection $dates,
    ): void {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Daily Attendance Sheet');
        $sheet->setSheetView((new SheetView)
            ->setFreezeRow(7)
            ->setFreezeColumn('F')
            ->setZoomScale(85));

        $sheet->setColumnWidth(16, 1); // Trainee ID
        $sheet->setColumnWidth(28, 2); // Trainee Name
        $sheet->setColumnWidth(30, 3); // Email
        $sheet->setColumnWidth(10, 4); // Schedule
        $sheet->setColumnWidth(14, 5); // Rate %
        $sheet->setColumnWidth(8, 6);  // P
        $sheet->setColumnWidth(8, 7);  // L
        $sheet->setColumnWidth(8, 8);  // A
        $sheet->setColumnWidth(8, 9);  // E
        $sheet->setColumnWidthForRange(14, 10, max(10, $dates->count() + 9));

        $this->writeSheetHeading($writer, 'MCARE Caregiving NC II - Daily Attendance Sheet', $batch, $schedule);

        $headers = [
            'Trainee ID',
            'Trainee Name',
            'Email',
            'Class',
            'Attendance Rate',
            'P',
            'L',
            'A',
            'E',
        ];

        foreach ($dates as $date) {
            $headers[] = Carbon::parse($date)->format('M d (D)');
        }

        $writer->addRow(Row::fromValues($headers, $this->headerStyle()));

        foreach ($trainees as $trainee) {
            $attendances = $trainee->attendances
                ->where('training_batch_id', $batch->id)
                ->whereNull('quiz_id')
                ->keyBy(fn ($a) => Carbon::parse($a->attendance_date)->toDateString());

            $present = $attendances->where('status', 'present')->count();
            $late = $attendances->where('status', 'late')->count();
            $absent = $attendances->where('status', 'absent')->count();
            $excused = $attendances->where('status', 'excused')->count();
            $totalRecorded = $dates->count();
            $rate = $totalRecorded > 0 ? round((($present + $late) / $totalRecorded) * 100, 1) : 0;

            $values = [
                'MCARE-TRN-'.str_pad((string) $trainee->id, 5, '0', STR_PAD_LEFT),
                trim(($trainee->last_name ?? '').', '.($trainee->first_name ?? '').' '.($trainee->middle_name ?? '')),
                $trainee->email ?? $trainee->user?->email ?? '-',
                $trainee->schedule_preference ?: 'AM',
                $rate / 100,
                $present,
                $late,
                $absent,
                $excused,
            ];

            $columnStyles = [
                4 => $this->percentageStyle(),
                5 => $this->statusStyle('present'),
                6 => $this->statusStyle('late'),
                7 => $this->statusStyle('absent'),
                8 => $this->statusStyle('excused'),
            ];

            foreach ($dates as $index => $date) {
                $status = $attendances->get($date)?->status;
                $values[] = $this->statusSymbol($status);
                $columnStyles[$index + 9] = $this->statusStyle($status);
            }

            $writer->addRow(Row::fromValuesWithStyles($values, null, $columnStyles));
        }
    }

    /**
     * @param  Collection<int, EnrollmentApplication>  $trainees
     * @param  Collection<int, string>  $dates
     */
    private function writeComplianceSummarySheet(
        Writer $writer,
        TrainingBatch $batch,
        ?string $schedule,
        Collection $trainees,
        Collection $dates,
    ): void {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('TESDA Compliance Roster');
        $sheet->setSheetView((new SheetView)
            ->setFreezeRow(7)
            ->setFreezeColumn('D')
            ->setZoomScale(85));

        $sheet->setColumnWidth(16, 1);
        $sheet->setColumnWidth(28, 2);
        $sheet->setColumnWidth(30, 3);
        $sheet->setColumnWidth(10, 4);
        $sheet->setColumnWidth(14, 5);
        $sheet->setColumnWidth(12, 6);
        $sheet->setColumnWidth(12, 7);
        $sheet->setColumnWidth(12, 8);
        $sheet->setColumnWidth(12, 9);
        $sheet->setColumnWidth(16, 10);
        $sheet->setColumnWidth(20, 11);

        $this->writeSheetHeading($writer, 'MCARE Caregiving NC II - TESDA Attendance Compliance Roster', $batch, $schedule);

        $headers = [
            'Trainee ID',
            'Trainee Name',
            'Email',
            'Class',
            'Total Sessions',
            'Present (P)',
            'Late (L)',
            'Absent (A)',
            'Excused (E)',
            'Attendance Rate',
            'TESDA Status',
        ];

        $writer->addRow(Row::fromValues($headers, $this->headerStyle()));

        foreach ($trainees as $trainee) {
            $attendances = $trainee->attendances
                ->where('training_batch_id', $batch->id)
                ->whereNull('quiz_id')
                ->keyBy(fn ($a) => Carbon::parse($a->attendance_date)->toDateString());

            $present = $attendances->where('status', 'present')->count();
            $late = $attendances->where('status', 'late')->count();
            $absent = $attendances->where('status', 'absent')->count();
            $excused = $attendances->where('status', 'excused')->count();
            $totalRecorded = $dates->count();
            $rate = $totalRecorded > 0 ? round((($present + $late) / $totalRecorded) * 100, 1) : 0;
            $isCompliant = $rate >= 80;

            $values = [
                'MCARE-TRN-'.str_pad((string) $trainee->id, 5, '0', STR_PAD_LEFT),
                trim(($trainee->last_name ?? '').', '.($trainee->first_name ?? '').' '.($trainee->middle_name ?? '')),
                $trainee->email ?? $trainee->user?->email ?? '-',
                $trainee->schedule_preference ?: 'AM',
                $totalRecorded,
                $present,
                $late,
                $absent,
                $excused,
                $rate / 100,
                $isCompliant ? 'COMPLIANT' : 'AT RISK (<80%)',
            ];

            $columnStyles = [
                9 => $this->percentageStyle(),
                10 => $isCompliant ? $this->statusStyle('present') : $this->statusStyle('absent'),
            ];

            $writer->addRow(Row::fromValuesWithStyles($values, null, $columnStyles));
        }
    }

    private function writeLegendSheet(Writer $writer): void
    {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Legend & TESDA Guidelines');
        $sheet->setColumnWidth(14, 1);
        $sheet->setColumnWidth(28, 2);
        $sheet->setColumnWidth(60, 3);

        $writer->addRow(Row::fromValues(['Attendance Status Code Legend & Policy Guidelines'], (new Style)
            ->setFontBold()
            ->setFontSize(14)
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor('6D28D9')));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues(['Code', 'Status', 'Description'], $this->headerStyle()));
        $writer->addRow(Row::fromValuesWithStyles(['P', 'Present', 'Attended the synchronous session on time or completed online session check-in.'], null, [0 => $this->statusStyle('present')]));
        $writer->addRow(Row::fromValuesWithStyles(['L', 'Late', 'Arrived after session commencement but attended majority of class hours.'], null, [0 => $this->statusStyle('late')]));
        $writer->addRow(Row::fromValuesWithStyles(['A', 'Absent', 'Unexcused non-attendance for the scheduled training session.'], null, [0 => $this->statusStyle('absent')]));
        $writer->addRow(Row::fromValuesWithStyles(['E', 'Excused', 'Excused absence with valid justification or medical notice.'], null, [0 => $this->statusStyle('excused')]));
        $writer->addRow(Row::fromValuesWithStyles(['-', 'No Session / Not Recorded', 'No session scheduled or attendance not yet logged.'], null, [0 => $this->statusStyle(null)]));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues(['TESDA Benchmark Policy:', 'Minimum 80% Attendance Required', 'Trainees must achieve at least 80% total attendance to be eligible for Institutional Assessment, Certificate of Training Completion (COTC), and TESDA National Assessment.'], $this->noticeStyle()));
    }

    private function writeSheetHeading(
        Writer $writer,
        string $title,
        TrainingBatch $batch,
        ?string $schedule,
    ): void {
        $writer->addRow($this->titleRow($title));
        $writer->addRow(Row::fromValues(['Batch', $batch->name.' '.$batch->year], $this->metadataStyle()));
        $writer->addRow(Row::fromValues(['Trainer', $batch->trainer?->name ?? 'Unassigned'], $this->metadataStyle()));
        $writer->addRow(Row::fromValues(['Class Filter', $schedule ?: 'AM and PM (All Schedules)'], $this->metadataStyle()));
        $writer->addRow(Row::fromValues(['Generated On', now()->format('Y-m-d h:i A').' ('.config('app.timezone').')'], $this->metadataStyle()));
        $writer->addRow(Row::fromValues(['Notice', 'Active enrolled trainees only. Official record for TESDA compliance.'], $this->noticeStyle()));
    }

    private function traineeQuery(TrainingBatch $batch, ?string $schedule): Builder
    {
        return EnrollmentApplication::query()
            ->with(['attendances' => fn ($q) => $q->where('training_batch_id', $batch->id)])
            ->where('training_batch_id', $batch->id)
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->where('learning_status', '!=', EnrollmentApplication::LEARNING_GRADUATED)
            ->when($schedule, fn ($query, $class) => $query->where('schedule_preference', $class))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id');
    }

    private function statusSymbol(?string $status): string
    {
        return match ($status) {
            'present' => 'P',
            'late' => 'L',
            'absent' => 'A',
            'excused' => 'E',
            default => '-',
        };
    }

    private function titleRow(string $title): Row
    {
        $row = Row::fromValues([$title], (new Style)
            ->setFontBold()
            ->setFontSize(15)
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor('6D28D9')
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER));
        $row->setHeight(28);

        return $row;
    }

    private function headerStyle(): Style
    {
        return (new Style)
            ->setFontBold()
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor('4C1D95')
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->setShouldWrapText();
    }

    private function metadataStyle(): Style
    {
        return (new Style)->setBackgroundColor('F5F3FF');
    }

    private function noticeStyle(): Style
    {
        return (new Style)
            ->setFontBold()
            ->setFontColor('78350F')
            ->setBackgroundColor('FFFBEB')
            ->setShouldWrapText();
    }

    private function percentageStyle(): Style
    {
        return (new Style)
            ->setFontBold()
            ->setFormat('0%')
            ->setCellAlignment(CellAlignment::CENTER);
    }

    private function statusStyle(?string $status): Style
    {
        [$background, $font] = match ($status) {
            'present' => ['DCFCE7', '166534'],
            'late' => ['FEF3C7', '92400E'],
            'absent' => ['FEE2E2', '991B1B'],
            'excused' => ['DBEAFE', '1E40AF'],
            default => ['F1F5F9', '475569'],
        };

        return (new Style)
            ->setFontBold()
            ->setFontColor($font)
            ->setBackgroundColor($background)
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }
}
