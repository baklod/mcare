<?php

namespace App\Services;

use App\Models\EnrollmentApplication;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class TesdaRegistrationPdfService
{
    private Fpdi $pdf;

    public function generate(EnrollmentApplication $application): string
    {
        $this->pdf = new Fpdi('P', 'pt');
        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->SetMargins(0, 0, 0);
        $this->pdf->SetTextColor(0, 0, 0);

        $template = resource_path('pdf-templates/tesda-dpa-form-1-mis-03-01.pdf');
        abort_unless(is_file($template), 500, 'The TESDA registration form template is missing.');

        $pageCount = $this->pdf->setSourceFile($template);
        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $templateId = $this->pdf->importPage($pageNumber);
            $size = $this->pdf->getTemplateSize($templateId);
            $this->pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $this->pdf->useTemplate($templateId);
            $pageNumber === 1 ? $this->fillProfile($application) : $this->fillConsent($application);
        }

        return $this->pdf->Output('S');
    }

    public function filename(EnrollmentApplication $application): string
    {
        $name = str($application->last_name.'-'.$application->first_name)->ascii()->slug('-')->upper();
        return "TESDA-MIS-03-01-{$name}-{$application->id}.pdf";
    }

    private function fillProfile(EnrollmentApplication $a): void
    {
        $birthDate = $a->birth_date;
        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->Rect(453, 217, 106, 15, 'F');
        $this->text(452, 221, 108, $a->created_at?->format('m/d/y') ?? now()->format('m/d/y'));
        $this->text(112, 259, 185, trim($a->last_name.' '.$a->extension_name));
        $this->text(304, 259, 172, $a->first_name);
        $this->text(480, 259, 87, $a->middle_name);
        $this->text(112, 303, 185, $a->street, 7.5);
        $this->text(304, 303, 172, $a->barangay, 7.5);
        $this->text(480, 303, 87, $a->zip_code, 7.5);
        $this->text(112, 351, 185, $a->city, 7.5);
        $this->text(304, 351, 172, $a->province, 7.5);
        $this->text(480, 351, 87, $a->region, 7.5);
        $this->text(112, 382, 185, $a->email, 7);
        $this->text(304, 382, 172, $a->contact_number, 7.5);
        $this->text(480, 382, 87, $a->nationality, 7.5);

        $this->markFor($a->gender, ['Male' => [37.5, 469], 'Female' => [37.5, 481]]);
        $this->markFor($a->civil_status, [
            'Single' => [130, 469], 'Married' => [130, 481],
            'Separated/Divorced/Annulled' => [130, 493], 'Widow/er' => [130, 505],
            'Common Law/Live-in' => [130, 517],
        ]);
        $this->markFor($a->employment_status, [
            'Wage-Employed' => [271, 481], 'Underemployed' => [271, 493],
            'Self-Employed' => [271, 529], 'Unemployed' => [271, 541],
        ]);
        $this->markFor($a->employment_type, [
            'Regular' => [470, 481], 'Casual' => [399, 493], 'Job Order' => [470, 493],
            'Probationary' => [399, 505], 'Permanent' => [470, 505],
            'Contractual' => [399, 517], 'Temporary' => [470, 517],
        ]);

        if ($birthDate) {
            $this->text(100, 554, 121, $birthDate->format('F'));
            $this->text(235, 554, 106, $birthDate->format('d'));
            $this->text(356, 554, 107, $birthDate->format('Y'));
            $this->text(476, 554, 91, (string) $birthDate->age);
        }
        $this->text(100, 596, 182, $a->birthplace_city, 7.5);
        $this->text(293, 596, 159, $a->birthplace_province, 7.5);
        $this->text(461, 596, 106, $a->birthplace_region, 7.5);

        $this->markFor($a->educational_attainment, [
            'No Grade Completed' => [30.5, 656], 'Elementary Undergraduate' => [30.5, 676],
            'Elementary Graduate' => [30.5, 692], 'High School Undergraduate' => [30.5, 713],
            'High School Graduate' => [30.5, 733], 'Junior High (K-12)' => [177, 656],
            'Senior High (K-12)' => [177, 676],
            'Post-Secondary/Technical Vocational Undergraduate' => [177, 692],
            'Post-Secondary/Technical Vocational Graduate' => [177, 713],
            'College Undergraduate' => [398, 656], 'College Graduate' => [398, 676],
            'Masteral' => [398, 692], 'Doctorate' => [398, 713],
        ]);
        $this->text(121, 766, 185, $a->guardian_name, 7.5);
        $this->text(308, 766, 259, $a->guardian_address, 7.2);
        $this->image($a->id_photo_path, 456, 116, 103, 68);
    }

    private function fillConsent(EnrollmentApplication $a): void
    {
        $this->markFor($a->classification, [
            '4Ps Beneficiary' => [30, 82], 'Displaced Worker' => [30, 102],
            'Industry Worker' => [30, 142], 'Out-of-School Youth' => [30, 162],
            'TESDA Alumni' => [30, 202], 'Victim of Natural Disaster/Calamity' => [30, 222],
            'Overseas Filipino Worker' => [207, 162], 'Returning/Repatriated OFW' => [207, 182],
            'TVET Trainer' => [207, 202], 'Student' => [392, 182], 'Others' => [392, 222],
        ]);
        $this->markFor($a->disability_type, [
            'Mental/Intellectual' => [48, 265], 'Hearing Disability' => [48, 278],
            'Psychosocial Disability' => [48, 292], 'Visual Disability' => [207, 265],
            'Speech Impairment' => [207, 278], 'Disability Due to Chronic Illness' => [207, 292],
            'Orthopedic Disability' => [407, 265], 'Multiple Disabilities' => [407, 278],
            'Learning Disability' => [407, 292],
        ]);
        $this->markFor($a->disability_cause, [
            'Congenital/Inborn' => [48, 326], 'Illness' => [207, 326], 'Injury' => [407, 326],
        ]);
        $this->text(26, 355, 540, $a->program ?: 'Caregiving NC II', 8, 'L');
        $this->text(26, 395, 540, $a->scholarship_type, 8, 'L');
        $a->privacy_consent ? $this->mark(200, 486) : $this->mark(320, 486);

        // Private applicant assets are embedded only in this admin-generated document.
        $this->image($a->signature_path, 42, 535, 205, 38);
        $this->text(42, 576, 205, $a->signature_name ?: $this->fullName($a), 7.5);
        $this->text(254, 576, 96, $a->date_accomplished?->format('m/d/Y'), 7.5);
        $this->image($a->id_photo_path, 407, 548, 96, 84);
    }

    private function fullName(EnrollmentApplication $a): string
    {
        return trim(implode(' ', array_filter([$a->first_name, $a->middle_name, $a->last_name, $a->extension_name])));
    }

    private function text(float $x, float $y, float $width, mixed $value, float $size = 8, string $align = 'C'): void
    {
        $value = trim((string) $value);
        if ($value === '') return;
        $this->pdf->SetFont('Helvetica', '', $size);
        $this->pdf->SetXY($x, $y);
        $this->pdf->Cell($width, $size + 2, $this->latin($value), 0, 0, $align);
    }

    private function markFor(?string $value, array $positions): void
    {
        if ($value !== null && isset($positions[$value])) $this->mark(...$positions[$value]);
    }

    private function mark(float $x, float $y): void
    {
        $this->pdf->SetFont('Helvetica', 'B', 7.5);
        $this->pdf->SetXY($x - 3, $y - 7);
        $this->pdf->Cell(8, 8, 'X', 0, 0, 'C');
    }

    private function image(?string $path, float $x, float $y, float $width, float $height): void
    {
        if (! $path || ! Storage::disk('local')->exists($path)) return;
        $absolute = Storage::disk('local')->path($path);
        $info = @getimagesize($absolute);
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) return;
        $this->pdf->Image($absolute, $x, $y, $width, $height);
    }

    private function latin(string $value): string
    {
        $converted = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $value);
        return $converted === false ? (preg_replace('/[^\x20-\x7E]/', '', $value) ?? '') : $converted;
    }
}
