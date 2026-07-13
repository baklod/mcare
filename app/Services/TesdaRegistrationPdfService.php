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
        // Cover only the template placeholder with a paper-toned patch, then keep the date high in its box.
        $this->pdf->SetFillColor(244, 246, 243);
        $this->pdf->Rect(482, 220, 50, 9, 'F');
        $this->text(452, 214.5, 108, $a->created_at?->format('m/d/y') ?? now()->format('m/d/y'), 10);
        $this->text(112, 263, 185, trim($a->last_name.' '.$a->extension_name), 9);
        $this->text(304, 263, 172, $a->first_name, 9);
        $this->text(480, 263, 87, $a->middle_name, 9);
        $this->text(112, 309, 185, $this->streetLine($a), 9, 'C', 6.5);
        $this->text(304, 309, 172, $a->barangay, 9);
        $this->text(480, 309, 87, $a->zip_code, 9);
        $this->text(112, 355, 185, $a->city, 9);
        $this->text(304, 355, 172, $a->province, 9);
        $this->text(480, 355, 87, $a->region, 9);
        $this->text(112, 385, 185, $a->email, 8.5, 'C', 6.5);
        $this->text(304, 385, 172, $a->contact_number, 9);
        $this->text(480, 385, 87, $a->nationality, 9);

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
            $this->text(100, 554, 121, $birthDate->format('F'), 9);
            $this->text(235, 554, 106, $birthDate->format('d'), 9);
            $this->text(356, 554, 107, $birthDate->format('Y'), 9);
            $this->text(476, 554, 91, (string) $birthDate->age, 9);
        }
        $this->text(100, 596, 182, $a->birthplace_city, 9);
        $this->text(293, 596, 159, $a->birthplace_province, 9);
        $this->text(461, 596, 106, $a->birthplace_region, 9);

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
        $this->text(121, 766, 185, $a->guardian_name, 8.5, 'C', 6.5);
        $this->text(308, 766, 259, $a->guardian_address, 8.5, 'C', 6.5);
        $this->imageContained($a->id_photo_path, 456, 116, 103, 68);
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
        $this->imageContained($a->signature_path, 52, 535, 184, 34);
        $this->text(42, 574, 205, $a->signature_name ?: $this->fullName($a), 9, 'C', 7);
        $this->text(254, 574, 96, $a->date_accomplished?->format('m/d/Y'), 9);

        // Keep the 1x1 photo inside its own upper box; the lower box remains for a physical thumbmark.
        $this->imageContained($a->id_photo_path, 408, 549, 94, 74);
    }

    private function fullName(EnrollmentApplication $a): string
    {
        return trim(implode(' ', array_filter([$a->first_name, $a->middle_name, $a->last_name, $a->extension_name])));
    }

    private function streetLine(EnrollmentApplication $a): string
    {
        $street = trim((string) $a->street);
        $locationValues = array_filter([
            $a->barangay,
            $a->city,
            $a->province,
            $a->region,
            $a->zip_code,
        ]);
        $excluded = array_map(fn ($value) => $this->comparisonKey((string) $value), $locationValues);
        $segments = preg_split('/\s*,\s*/u', $street) ?: [$street];
        $streetOnly = array_values(array_filter($segments, function ($segment) use ($excluded) {
            return $segment !== '' && ! in_array($this->comparisonKey($segment), $excluded, true);
        }));

        return $streetOnly === [] ? $street : implode(', ', $streetOnly);
    }

    private function comparisonKey(string $value): string
    {
        return str($value)->lower()->squish()->trim(' .,')->toString();
    }

    private function text(
        float $x,
        float $y,
        float $width,
        mixed $value,
        float $size = 9,
        string $align = 'C',
        float $minimumSize = 7
    ): void {
        $value = $this->latin(trim((string) $value));
        if ($value === '') {
            return;
        }

        $this->pdf->SetFont('Helvetica', '', $size);
        while ($size > $minimumSize && $this->pdf->GetStringWidth($value) > $width - 5) {
            $size -= 0.25;
            $this->pdf->SetFont('Helvetica', '', $size);
        }

        if ($this->pdf->GetStringWidth($value) > $width - 5) {
            $truncated = $value;
            while (strlen($truncated) > 1 && $this->pdf->GetStringWidth($truncated.'...') > $width - 5) {
                $truncated = rtrim(substr($truncated, 0, -1));
            }
            $value = $truncated.'...';
        }

        $this->pdf->SetXY($x, $y);
        $this->pdf->Cell($width, $size + 2, $value, 0, 0, $align);
    }

    private function markFor(?string $value, array $positions): void
    {
        if ($value !== null && isset($positions[$value])) {
            $this->mark(...$positions[$value]);
        }
    }

    private function mark(float $x, float $y): void
    {
        $half = 3.1;
        $centerY = $y - 3.2;
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->SetLineWidth(1.1);
        $this->pdf->Line($x - $half, $centerY - $half, $x + $half, $centerY + $half);
        $this->pdf->Line($x + $half, $centerY - $half, $x - $half, $centerY + $half);
        $this->pdf->SetLineWidth(0.2);
    }

    private function imageContained(?string $path, float $x, float $y, float $width, float $height): void
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return;
        }
        $absolute = Storage::disk('local')->path($path);
        $info = @getimagesize($absolute);
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            return;
        }

        $scale = min($width / $info[0], $height / $info[1]);
        $renderWidth = $info[0] * $scale;
        $renderHeight = $info[1] * $scale;
        $renderX = $x + (($width - $renderWidth) / 2);
        $renderY = $y + (($height - $renderHeight) / 2);
        $this->pdf->Image($absolute, $renderX, $renderY, $renderWidth, $renderHeight);
    }

    private function latin(string $value): string
    {
        $converted = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $value);

        return $converted === false ? (preg_replace('/[^\x20-\x7E]/', '', $value) ?? '') : $converted;
    }
}
