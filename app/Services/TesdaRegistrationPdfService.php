<?php

namespace App\Services;

use App\Models\EnrollmentApplication;
use App\Models\PublicSiteSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Throwable;

class TesdaRegistrationPdfService
{
    private Fpdi $pdf;

    /** @var list<string> */
    private array $temporaryImages = [];

    private PublicSiteSetting $registrarSettings;

    public function generate(EnrollmentApplication $application, ?PublicSiteSetting $settings = null): string
    {
        $this->temporaryImages = [];
        $this->registrarSettings = $settings ?? $this->resolveRegistrarSettings();
        $this->pdf = new Fpdi('P', 'pt');
        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->SetMargins(0, 0, 0);
        $this->pdf->SetTextColor(0, 0, 0);

        $template = resource_path('pdf-templates/tesda-dpa-form-1-mis-03-01.pdf');
        abort_unless(is_file($template), 500, 'The TESDA registration form template is missing.');

        $application->loadMissing('user');

        try {
            $pageCount = $this->pdf->setSourceFile($template);
            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $templateId = $this->pdf->importPage($pageNumber);
                $size = $this->pdf->getTemplateSize($templateId);
                $this->pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $this->pdf->useTemplate($templateId);
                $pageNumber === 1 ? $this->fillProfile($application) : $this->fillConsent($application);
            }

            return $this->pdf->Output('S');
        } finally {
            $this->forgetTemporaryImages();
        }
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
        $this->boxed(452, 108, 212.6, 235.3, $a->created_at?->format('m/d/y') ?? now()->format('m/d/y'), 10, 'C', 7, 0);
        $this->boxed(112, 185, 259.4, 275.8, trim($a->last_name.' '.$a->extension_name), 9, 'C', 7, 0);
        $this->boxed(304, 172, 259.4, 275.8, $a->first_name, 9, 'C', 7, 0);
        $this->boxed(480, 87, 259.4, 275.8, $a->middle_name, 9, 'C', 7, 0);
        $this->boxed(112, 185, 288.5, 338.0, $this->streetLine($a), 9, 'C', 6.5, 0);
        $this->boxed(304, 172, 288.5, 338.0, $a->barangay, 9, 'C', 7, 0);
        $this->boxed(480, 87, 288.5, 338.0, $a->zip_code, 9, 'C', 7, 0);
        $this->boxed(112, 185, 350.7, 368.5, $a->city, 9, 'C', 7, 0);
        $this->boxed(304, 172, 350.7, 368.5, $a->province, 9, 'C', 7, 0);
        $this->boxed(480, 87, 350.7, 368.5, $a->region, 9, 'C', 7, 0);
        $this->boxed(112, 185, 380.3, 397.5, $a->email, 9, 'C', 6.5, 0);
        $this->boxed(304, 172, 380.3, 397.5, $a->contact_number, 9, 'C', 7, 0);
        $this->boxed(480, 87, 380.3, 397.5, $a->nationality, 9, 'C', 7, 0);

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
            $this->boxed(100, 121, 547.0, 568.0, $birthDate->format('F'));
            $this->boxed(235, 106, 547.0, 568.0, $birthDate->format('d'));
            $this->boxed(356, 107, 547.0, 568.0, $birthDate->format('Y'));
            $this->boxed(476, 91, 547.0, 568.0, (string) $birthDate->age);
        }
        $this->boxed(100, 182, 591.1, 608.8, $a->birthplace_city);
        $this->boxed(293, 159, 591.1, 608.8, $a->birthplace_province);
        $this->boxed(461, 106, 591.1, 608.8, $a->birthplace_region);

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
        $this->boxed(121, 185, 747.8, 768.0, $a->guardian_name, 9, 'C', 6.5);
        $this->boxed(308, 259, 747.8, 768.0, $a->guardian_address, 9, 'C', 6.5);
        $this->imageContained($this->idPhotoCandidates($a), 456, 116, 103, 68);
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
        $this->boxed(26, 540, 352.2, 371.7, $a->program ?: 'Caregiving NC II', 9, 'L');
        $this->boxed(26, 540, 390.3, 409.9, $a->scholarship_type, 9, 'L');
        $a->privacy_consent ? $this->mark(200, 486) : $this->mark(320, 486);

        // Private applicant assets are embedded only in this admin-generated document.
        $this->imageContained([$a->signature_path], 52, 528, 184, 32, true);
        $this->formName(42, 568, 205, $a->signature_name ?: $this->fullName($a));
        $this->formName(254, 568, 96, $a->date_accomplished?->format('m/d/Y'));

        // Keep the 1x1 photo inside its own upper box; the lower box remains for a physical thumbmark.
        $this->imageContained($this->idPhotoCandidates($a), 408, 549, 94, 74);

        $registrarName = $this->registrarSettings->registrarName();
        $registrarSignature = $this->registrarSettings->registrar_signature_path;
        if (filled($registrarName) || filled($registrarSignature)) {
            $this->imageContained([$registrarSignature], 52, 612, 184, 32, true);
            $this->formName(42, 648, 205, $registrarName);
            $this->formName(254, 648, 96, now()->format('m/d/Y'));
        }
    }

    private function resolveRegistrarSettings(): PublicSiteSetting
    {
        if (! Schema::hasTable('public_site_settings')) {
            return new PublicSiteSetting;
        }

        return PublicSiteSetting::current();
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

    private function boxed(
        float $x,
        float $width,
        float $boxTop,
        float $boxBottom,
        mixed $value,
        float $size = 9,
        string $align = 'C',
        float $minimumSize = 7,
        float $rise = 2.6
    ): void {
        $cellHeight = $size + 1;
        $y = (($boxTop + $boxBottom) / 2) - ($cellHeight / 2) - $rise;
        $this->text($x, $y, $width, $value, $size, $align, $minimumSize, 'B');
    }

    private function formName(float $x, float $y, float $width, mixed $value): void
    {
        $this->text($x, $y, $width, $value, 9, 'C', 7, 'B');
    }

    private function text(
        float $x,
        float $y,
        float $width,
        mixed $value,
        float $size = 9,
        string $align = 'C',
        float $minimumSize = 7,
        string $style = 'B'
    ): void {
        $value = $this->latin(trim((string) $value));
        if ($value === '') {
            return;
        }

        $this->pdf->SetFont('Helvetica', $style, $size);
        while ($size > $minimumSize && $this->pdf->GetStringWidth($value) > $width - 5) {
            $size -= 0.25;
            $this->pdf->SetFont('Helvetica', $style, $size);
        }

        if ($this->pdf->GetStringWidth($value) > $width - 5) {
            $truncated = $value;
            while (strlen($truncated) > 1 && $this->pdf->GetStringWidth($truncated.'...') > $width - 5) {
                $truncated = rtrim(substr($truncated, 0, -1));
            }
            $value = $truncated.'...';
        }

        $this->pdf->SetXY($x, $y);
        $this->pdf->Cell($width, $size + 1, $value, 0, 0, $align);
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

    /** @return list<string> */
    private function idPhotoCandidates(EnrollmentApplication $application): array
    {
        return array_values(array_filter([
            $application->id_photo_path,
            $application->user?->profile_photo_path,
        ], fn ($path) => filled($path)));
    }

    /** @param list<string>|string|null $paths */
    private function imageContained(
        array|string|null $paths,
        float $x,
        float $y,
        float $width,
        float $height,
        bool $transparent = false
    ): void {
        $candidates = array_values(array_unique(array_filter(
            is_array($paths) ? $paths : [$paths],
            fn ($path) => is_string($path) && $path !== ''
        )));

        foreach ($candidates as $path) {
            $prepared = $this->prepareEmbeddedImage($path, $width, $height, $transparent);
            if ($prepared === null) {
                continue;
            }

            try {
                $this->pdf->Image(
                    $prepared['path'],
                    $x + (($width - $prepared['width']) / 2),
                    $y + (($height - $prepared['height']) / 2),
                    $prepared['width'],
                    $prepared['height'],
                    $prepared['type']
                );

                return;
            } catch (Throwable) {
                // Try the next readable file so one bad photo does not blank the form.
            }
        }
    }

    /** @return array{path: string, width: float, height: float, type: string}|null */
    private function prepareEmbeddedImage(string $path, float $boxWidth, float $boxHeight, bool $transparent): ?array
    {
        $absolute = $this->resolveImagePath($path);
        if ($absolute === null) {
            return null;
        }

        $binary = @file_get_contents($absolute);
        if (! is_string($binary) || $binary === '') {
            return null;
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return null;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        if ($sourceWidth < 1 || $sourceHeight < 1) {
            imagedestroy($source);

            return null;
        }

        $dpi = 220;
        $maxWidth = max(80, (int) round($boxWidth * $dpi / 72));
        $maxHeight = max(80, (int) round($boxHeight * $dpi / 72));
        $scale = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1.0);
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));

        $canvas = imagecreatetruecolor($width, $height);
        if ($canvas === false) {
            imagedestroy($source);

            return null;
        }

        imagealphablending($canvas, false);
        if ($transparent) {
            imagesavealpha($canvas, true);
            $clear = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $width, $height, $clear);
        } else {
            imagefilledrectangle($canvas, 0, 0, $width, $height, imagecolorallocate($canvas, 255, 255, 255));
        }
        imagealphablending($canvas, true);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
        imagedestroy($source);

        if ($transparent) {
            $this->knockOutSignatureBackground($canvas, $width, $height);
            $cropped = $this->cropSignatureInk($canvas, $width, $height);
            imagedestroy($canvas);
            if ($cropped === null) {
                return null;
            }
            $canvas = $cropped['image'];
            $width = $cropped['width'];
            $height = $cropped['height'];
        }

        $base = tempnam(sys_get_temp_dir(), 'mcare-tesda-');
        if ($base === false) {
            imagedestroy($canvas);

            return null;
        }

        $extension = $transparent ? '.png' : '.jpg';
        $outputPath = $base.$extension;
        @unlink($base);

        $written = $transparent ? imagepng($canvas, $outputPath, 6) : imagejpeg($canvas, $outputPath, 88);
        imagedestroy($canvas);

        if (! $written || ! is_file($outputPath)) {
            @unlink($outputPath);

            return null;
        }

        $this->temporaryImages[] = $outputPath;
        $info = @getimagesize($outputPath);
        if (! $info || $info[0] < 1 || $info[1] < 1) {
            return null;
        }

        $fit = min($boxWidth / $info[0], $boxHeight / $info[1]);

        return [
            'path' => $outputPath,
            'width' => $info[0] * $fit,
            'height' => $info[1] * $fit,
            'type' => $transparent ? 'PNG' : 'JPG',
        ];
    }

    private function knockOutSignatureBackground(\GdImage $canvas, int $width, int $height): void
    {
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $clear = imagecolorallocatealpha($canvas, 0, 0, 0, 127);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgba = imagecolorat($canvas, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                $red = ($rgba >> 16) & 0xFF;
                $green = ($rgba >> 8) & 0xFF;
                $blue = $rgba & 0xFF;
                $luma = (0.2126 * $red) + (0.7152 * $green) + (0.0722 * $blue);

                if ($alpha > 100 || $luma > 220) {
                    imagesetpixel($canvas, $x, $y, $clear);
                }
            }
        }
    }

    /** @return array{image: \GdImage, width: int, height: int}|null */
    private function cropSignatureInk(\GdImage $canvas, int $width, int $height): ?array
    {
        $minX = $width;
        $minY = $height;
        $maxX = -1;
        $maxY = -1;

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $alpha = (imagecolorat($canvas, $x, $y) & 0x7F000000) >> 24;
                if ($alpha > 110) {
                    continue;
                }
                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }

        if ($maxX < $minX || $maxY < $minY) {
            return null;
        }

        $pad = 2;
        $minX = max(0, $minX - $pad);
        $minY = max(0, $minY - $pad);
        $maxX = min($width - 1, $maxX + $pad);
        $maxY = min($height - 1, $maxY + $pad);
        $cropWidth = max(1, $maxX - $minX + 1);
        $cropHeight = max(1, $maxY - $minY + 1);

        $cropped = imagecreatetruecolor($cropWidth, $cropHeight);
        if ($cropped === false) {
            return null;
        }

        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        imagefilledrectangle($cropped, 0, 0, $cropWidth, $cropHeight, imagecolorallocatealpha($cropped, 0, 0, 0, 127));
        imagealphablending($cropped, true);
        imagecopy($cropped, $canvas, 0, 0, $minX, $minY, $cropWidth, $cropHeight);

        return ['image' => $cropped, 'width' => $cropWidth, 'height' => $cropHeight];
    }

    private function resolveImagePath(string $path): ?string
    {
        $normalized = str_replace('\\', '/', trim($path));
        $relative = ltrim($normalized, '/');
        $relative = preg_replace('#^(?:storage/app/private/|app/private/|private/)#', '', $relative) ?? $relative;

        foreach (array_unique(array_filter([$relative, $normalized, $path])) as $candidate) {
            if (Storage::disk('local')->exists($candidate)) {
                return Storage::disk('local')->path($candidate);
            }
        }

        $publicRelative = str_starts_with($relative, 'storage/')
            ? substr($relative, strlen('storage/'))
            : $relative;

        if ($publicRelative !== '' && Storage::disk('public')->exists($publicRelative)) {
            return Storage::disk('public')->path($publicRelative);
        }

        foreach ([$path, $normalized] as $absolute) {
            if (is_file($absolute)) {
                return $absolute;
            }
        }

        return null;
    }

    private function forgetTemporaryImages(): void
    {
        foreach ($this->temporaryImages as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->temporaryImages = [];
    }

    private function latin(string $value): string
    {
        $converted = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $value);

        return $converted === false ? (preg_replace('/[^\x20-\x7E]/', '', $value) ?? '') : $converted;
    }
}
