<?php

namespace Tests\Unit;

use App\Models\EnrollmentApplication;
use App\Models\PublicSiteSetting;
use App\Models\User;
use App\Services\TesdaRegistrationPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TesdaRegistrationPdfServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_id_photo_is_embedded_as_jpeg_and_signature_stays_visible(): void
    {
        Storage::fake('local');

        $photoPath = 'enrollment-documents/1/id-photo.png';
        $signaturePath = 'enrollment-documents/1/signature.png';
        $this->storeJpeg('local', $photoPath, 640, 480);
        $this->storeTransparentPng($signaturePath);

        $withoutImages = $this->application();
        $withImages = $this->application([
            'id_photo_path' => $photoPath,
            'signature_path' => $signaturePath,
        ]);

        $service = app(TesdaRegistrationPdfService::class);
        $blankPdf = $service->generate($withoutImages);
        $filledPdf = $service->generate($withImages);

        $this->assertStringStartsWith('%PDF-', $filledPdf);
        $this->assertGreaterThan($this->pdfImageCount($blankPdf), $this->pdfImageCount($filledPdf));
        $this->assertStringContainsString('/DCTDecode', $filledPdf);
    }

    public function test_missing_private_photo_falls_back_to_the_public_profile_photo(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->storeJpeg('public', 'avatars/9/face.jpg', 200, 200);

        $application = $this->application([
            'id_photo_path' => 'enrollment-documents/9/missing.jpg',
        ]);
        $application->setRelation('user', new User([
            'id' => 9,
            'profile_photo_path' => 'avatars/9/face.jpg',
        ]));

        $blankPdf = app(TesdaRegistrationPdfService::class)->generate($this->application());
        $filledPdf = app(TesdaRegistrationPdfService::class)->generate($application);

        $this->assertGreaterThan($this->pdfImageCount($blankPdf), $this->pdfImageCount($filledPdf));
        $this->assertStringContainsString('/DCTDecode', $filledPdf);
    }

    public function test_registrar_signature_is_embedded_on_the_noted_by_line(): void
    {
        Storage::fake('local');

        $registrarPath = 'organization-assets/registrar-signature.png';
        $this->storeTransparentPng($registrarPath);

        $settings = new PublicSiteSetting([
            'registrar_name' => 'Salvacion A. Collao',
            'registrar_signature_path' => $registrarPath,
        ]);

        $service = app(TesdaRegistrationPdfService::class);
        $blankPdf = $service->generate($this->application());
        $filledPdf = $service->generate($this->application(), $settings);

        $this->assertGreaterThan($this->pdfImageCount($blankPdf), $this->pdfImageCount($filledPdf));
    }

    public function test_missing_images_still_produce_a_form(): void
    {
        Storage::fake('local');

        $pdf = app(TesdaRegistrationPdfService::class)->generate($this->application([
            'id_photo_path' => 'enrollment-documents/1/missing.jpg',
            'signature_path' => 'enrollment-documents/1/missing-signature.png',
        ]));

        $this->assertStringStartsWith('%PDF-', $pdf);
    }

    private function application(array $overrides = []): EnrollmentApplication
    {
        $application = new EnrollmentApplication(array_merge([
            'first_name' => 'Maria',
            'middle_name' => 'Reyes',
            'last_name' => 'Santos',
            'program' => 'Caregiving NC II',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'privacy_consent' => true,
            'signature_name' => 'Maria Reyes Santos',
            'date_accomplished' => '2026-07-12',
        ], $overrides));
        $application->setRelation('user', new User(['id' => 1]));

        return $application;
    }

    private function storeJpeg(string $disk, string $path, int $width, int $height): void
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocate($image, 30, 90, 180));
        ob_start();
        imagejpeg($image, null, 90);
        Storage::disk($disk)->put($path, (string) ob_get_clean());
        imagedestroy($image);
    }

    private function storeTransparentPng(string $path): void
    {
        $image = imagecreatetruecolor(240, 80);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefilledrectangle($image, 0, 0, 240, 80, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagealphablending($image, true);
        $ink = imagecolorallocate($image, 20, 20, 20);
        imageline($image, 12, 60, 90, 18, $ink);
        imageline($image, 90, 18, 228, 58, $ink);
        ob_start();
        imagepng($image);
        Storage::disk('local')->put($path, (string) ob_get_clean());
        imagedestroy($image);
    }

    private function pdfImageCount(string $pdf): int
    {
        return preg_match_all('/\/Subtype\s*\/Image/', $pdf) ?: 0;
    }
}
